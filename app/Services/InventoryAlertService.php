<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryAlertService extends BaseService
{
    protected string $cachePrefix = 'inventory_alerts:';

    /**
     * Get all low stock products across all stores
     */
    public function getLowStockProducts(): array
    {
        return $this->remember('low_stock_all', function () {
            $stores = Store::all();
            $alerts = [];

            foreach ($stores as $store) {
                $lowStockProducts = Product::query()->active()
                    ->lowStock($store->id)
                    ->with(['category', 'supplier', 'stores' => function ($query) use ($store) {
                        $query->where('stores.id', $store->id);
                    }])
                    ->get();

                if ($lowStockProducts->isNotEmpty()) {
                    $alerts[] = [
                        'store' => $store,
                        'products' => $lowStockProducts->map(function ($product) use ($store) {
                            $storeData = $product->stores->first();

                            return [
                                'product' => $product,
                                'current_stock' => $storeData->pivot->stock,
                                'threshold' => $storeData->pivot->low_stock_threshold,
                                'deficit' => $storeData->pivot->low_stock_threshold - $storeData->pivot->stock,
                                'severity' => $this->calculateSeverity($storeData->pivot->stock, $storeData->pivot->low_stock_threshold),
                                'suggested_order_qty' => $this->calculateSuggestedOrderQuantity($product, $store),
                            ];
                        }),
                    ];
                }
            }

            return $alerts;
        }, 300); // 5 minutes cache
    }

    /**
     * Get low stock products for a specific store
     */
    public function getLowStockForStore(int $storeId): Collection
    {
        return $this->remember("store_{$storeId}_low_stock", function () use ($storeId) {
            return Product::query()->active()
                ->lowStock($storeId)
                ->with(['category', 'supplier', 'stores' => function ($query) use ($storeId) {
                    $query->where('stores.id', $storeId);
                }])
                ->get()
                ->map(function ($product) use ($storeId) {
                    $storeData = $product->stores->first();

                    return [
                        'product' => $product,
                        'current_stock' => $storeData->pivot->stock,
                        'threshold' => $storeData->pivot->low_stock_threshold,
                        'deficit' => $storeData->pivot->low_stock_threshold - $storeData->pivot->stock,
                        'severity' => $this->calculateSeverity($storeData->pivot->stock, $storeData->pivot->low_stock_threshold),
                        'suggested_order_qty' => $this->calculateSuggestedOrderQuantity($product, Store::query()->find($storeId)),
                        'last_sold' => $this->getLastSoldDate($product->id, $storeId),
                        'sales_velocity' => $this->calculateSalesVelocity($product->id, $storeId),
                    ];
                });
        }, 300);
    }

    /**
     * Check if product needs immediate attention (critical low stock)
     */
    public function getCriticalLowStock(): Collection
    {
        return $this->remember('critical_low_stock', function () {
            $stores = Store::all();
            $critical = collect();

            foreach ($stores as $store) {
                $products = Product::active()
                    ->whereHas('stores', function ($q) use ($store) {
                        $q->where('store_id', $store->id)
                            ->whereRaw('product_store.stock = 0 OR product_store.stock <= (product_store.low_stock_threshold * 0.5)');
                    })
                    ->with(['category', 'stores' => function ($query) use ($store) {
                        $query->where('stores.id', $store->id);
                    }])
                    ->get();

                foreach ($products as $product) {
                    $storeData = $product->stores->first();
                    $critical->push([
                        'store' => $store,
                        'product' => $product,
                        'current_stock' => $storeData->pivot->stock,
                        'threshold' => $storeData->pivot->low_stock_threshold,
                        'is_out_of_stock' => $storeData->pivot->stock == 0,
                        'suggested_order_qty' => $this->calculateSuggestedOrderQuantity($product, $store),
                    ]);
                }
            }

            return $critical;
        }, 180); // 3 minutes cache for critical alerts
    }

    /**
     * Generate reorder suggestions
     */
    public function generateReorderSuggestions(int $storeId): Collection
    {
        return $this->remember("reorder_suggestions_$storeId", function () use ($storeId) {
            $lowStockProducts = $this->getLowStockForStore($storeId);
            $store = Store::query()->find($storeId);

            return $lowStockProducts->map(function ($item) {
                $product = $item['product'];
                $suggestedQty = $item['suggested_order_qty'];
                $estimatedCost = $suggestedQty * $product->cost;

                return [
                    'product' => $product,
                    'current_stock' => $item['current_stock'],
                    'suggested_quantity' => $suggestedQty,
                    'estimated_cost' => $estimatedCost,
                    'supplier' => $product->supplier,
                    'priority' => $item['severity'],
                    'sales_velocity' => $item['sales_velocity'],
                    'days_of_stock_remaining' => $this->calculateDaysOfStockRemaining($item['current_stock'], $item['sales_velocity']),
                ];
            })->sortByDesc('priority');
        }, 600); // 10 minutes cache
    }

    /**
     * Send low stock alerts to managers
     */
    public function sendLowStockAlerts(): int
    {
        $lowStockData = $this->getLowStockProducts();
        $criticalStock = $this->getCriticalLowStock();

        if (empty($lowStockData) && $criticalStock->isEmpty()) {
            return 0;
        }

        // Get managers and store managers to notify
        $managers = User::query()->whereIn('role', ['manager', 'admin'])->get();
        $alertsSent = 0;

        foreach ($managers as $manager) {
            try {
                $this->sendAlertEmail($manager, $lowStockData, $criticalStock);
                $this->createInAppNotification($manager, $lowStockData, $criticalStock);
                $alertsSent++;
            } catch (Exception $e) {
                $this->logError('Failed to send low stock alert', [
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logInfo('Low stock alerts sent', [
            'alerts_sent' => $alertsSent,
            'low_stock_stores' => count($lowStockData),
            'critical_products' => $criticalStock->count(),
        ]);

        return $alertsSent;
    }

    /**
     * Calculate severity level (1-5, where 5 is most critical)
     */
    private function calculateSeverity(int $currentStock, int $threshold): int
    {
        if ($currentStock == 0) {
            return 5; // Out of stock
        }

        $ratio = $currentStock / $threshold;

        if ($ratio <= 0.2) {
            return 4;
        } // 20% or less of threshold
        if ($ratio <= 0.5) {
            return 3;
        } // 50% or less of threshold
        if ($ratio <= 0.8) {
            return 2;
        } // 80% or less of threshold
        if ($ratio <= 1.0) {
            return 1;
        } // At or below threshold

        return 0; // Above threshold (shouldn't happen in low stock query)
    }

    /**
     * Calculate suggested order quantity based on sales velocity and lead time
     */
    private function calculateSuggestedOrderQuantity(Product $product, Store $store): int
    {
        $salesVelocity = $this->calculateSalesVelocity($product->id, $store->id);
        $leadTimeDays = 7; // Default 1 week lead time
        $safetyStock = max(10, $salesVelocity * 3); // 3 days safety stock

        // Calculate quantity needed for lead time + safety stock
        $suggestedQty = ($salesVelocity * $leadTimeDays) + $safetyStock;

        // Ensure minimum order quantity
        $minOrderQty = $product->stores()->where('stores.id', $store->id)->first()?->pivot->low_stock_threshold ?? 10;

        return max($suggestedQty, $minOrderQty);
    }

    /**
     * Calculate average daily sales velocity for a product in a store
     */
    private function calculateSalesVelocity(int $productId, int $storeId, int $days = 30): float
    {
        return $this->remember("velocity_{$productId}_{$storeId}_$days", function () use ($productId, $storeId, $days) {
            $totalSold = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $productId)
                ->where('sales.store_id', $storeId)
                ->where('sales.created_at', '>=', now()->subDays($days))
                ->sum('sale_items.quantity');

            return $totalSold / $days;
        }, 3600); // 1 hour cache
    }

    /**
     * Get last sold date for a product
     */
    private function getLastSoldDate(int $productId, int $storeId): ?string
    {
        return $this->remember("last_sold_{$productId}_$storeId", function () use ($productId, $storeId) {
            $lastSale = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $productId)
                ->where('sales.store_id', $storeId)
                ->orderByDesc('sales.created_at')
                ->first();

            return $lastSale?->created_at;
        }, 3600);
    }

    /**
     * Calculate days of stock remaining
     */
    private function calculateDaysOfStockRemaining(int $currentStock, float $dailyVelocity): int
    {
        if ($dailyVelocity <= 0) {
            return 999; // Effectively infinite if no sales
        }

        return (int) ($currentStock / $dailyVelocity);
    }

    /**
     * Send email alert to manager
     */
    private function sendAlertEmail(User $manager, array $lowStockData, Collection $criticalStock): void
    {
        // This would integrate with your email system
        // For now, just log the alert
        $this->logInfo('Email alert would be sent', [
            'manager_id' => $manager->id,
            'low_stock_stores' => count($lowStockData),
            'critical_items' => $criticalStock->count(),
        ]);
    }

    /**
     * Create in-app notification
     */
    private function createInAppNotification(User $manager, array $lowStockData, Collection $criticalStock): void
    {
        $totalLowStock = collect($lowStockData)->sum(fn ($store) => $store['products']->count());

        // This would create a database notification
        $this->logInfo('In-app notification created', [
            'manager_id' => $manager->id,
            'total_low_stock' => $totalLowStock,
            'critical_items' => $criticalStock->count(),
        ]);
    }

    /**
     * Update low stock thresholds based on sales patterns
     */
    public function updateOptimalThresholds(int $storeId): int
    {
        $products = Product::query()->active()->whereHas('stores', function ($q) use ($storeId) {
            $q->where('stores.id', $storeId);
        })->get();

        $updated = 0;

        foreach ($products as $product) {
            $currentThreshold = $product->stores()->where('stores.id', $storeId)->first()?->pivot->low_stock_threshold ?? 10;
            $salesVelocity = $this->calculateSalesVelocity($product->id, $storeId, 60); // 60-day average

            // Calculate optimal threshold: (average daily sales * lead time) + safety stock
            $leadTime = 7; // days
            $safetyStockDays = 3;
            $optimalThreshold = ceil($salesVelocity * ($leadTime + $safetyStockDays));

            // Don't set threshold too low or too high
            $optimalThreshold = max(5, min($optimalThreshold, 100));

            // Only update if significantly different (more than 20% difference)
            if (abs($optimalThreshold - $currentThreshold) / $currentThreshold > 0.2) {
                $product->stores()->updateExistingPivot($storeId, [
                    'low_stock_threshold' => $optimalThreshold,
                ]);
                $updated++;

                $this->logInfo('Updated low stock threshold', [
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'old_threshold' => $currentThreshold,
                    'new_threshold' => $optimalThreshold,
                    'sales_velocity' => $salesVelocity,
                ]);
            }
        }

        return $updated;
    }

    /**
     * Clear inventory alerts cache
     */
    public function clearCache(): void
    {
        $cacheKeys = [
            'low_stock_all',
            'critical_low_stock',
        ];

        foreach ($cacheKeys as $key) {
            $this->forget($key);
        }

        // Clear store-specific caches
        $stores = Store::all();
        foreach ($stores as $store) {
            $this->forget("store_{$store->id}_low_stock");
            $this->forget("reorder_suggestions_$store->id");
        }
    }
}
