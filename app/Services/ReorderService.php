<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Store;
use App\Models\Supplier;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReorderService extends BaseService
{
    protected string $cachePrefix = 'reorder:';

    protected array $cacheTags = ['reorder', 'inventory'];

    public function __construct(private readonly InventoryAlertService $inventoryAlertService)
    {
        // No parent constructor to call; BaseService has none.
    }

    /**
     * Get comprehensive reorder list for a store
     */
    public function getReorderList(int $storeId): Collection
    {
        return $this->remember("list_$storeId", function () use ($storeId) {
            $lowStockProducts = $this->inventoryAlertService->getLowStockForStore($storeId);

            return $lowStockProducts->map(function (array $item) use ($storeId): array {
                $product = $item['product'];

                return [
                    'product' => $product,
                    'supplier' => $product->supplier,
                    'current_stock' => $item['current_stock'],
                    'threshold' => $item['threshold'],
                    'deficit' => $item['deficit'],
                    'suggested_order_qty' => $item['suggested_order_qty'],
                    'estimated_cost' => $item['suggested_order_qty'] * $product->cost,
                    'priority' => $item['severity'],
                    'sales_velocity' => $item['sales_velocity'],
                    'days_remaining' => $this->calculateDaysOfStockRemaining($item['current_stock'], $item['sales_velocity']),
                    'pending_orders' => $this->getPendingOrderQuantity($product->id, $storeId),
                    'last_ordered' => $this->getLastOrderDate($product->id),
                    'avg_lead_time' => $this->getAverageLeadTime($product->supplier_id),
                ];
            })->sortByDesc(fn (array $item): int|float =>
                // Sort by priority first, then by days remaining
                ($item['priority'] * 1000) + (100 - min($item['days_remaining'], 100)))->values();
        }, 300, ["store_$storeId"]);
    }

    /**
     * Get reorder list grouped by supplier
     */
    public function getReorderListBySupplier(int $storeId): Collection
    {
        return $this->remember("by_supplier_$storeId", function () use ($storeId) {
            $reorderList = $this->getReorderList($storeId);

            return $reorderList->groupBy('supplier.id')->map(function ($items, $supplierId): array {
                $supplier = $items->first()['supplier'];
                $totalCost = $items->sum('estimated_cost');

                return [
                    'supplier' => $supplier,
                    'items' => $items->values(),
                    'total_items' => $items->count(),
                    'total_cost' => $totalCost,
                    'high_priority_items' => $items->where('priority', '>=', 4)->count(),
                    'avg_lead_time' => $this->getAverageLeadTime($supplierId),
                ];
            })->sortByDesc('high_priority_items')->values();
        }, 300, ["store_$storeId"]);
    }

    /**
     * Create purchase order from reorder recommendations
     */
    public function createPurchaseOrderFromReorder(array $items, int $storeId, int $createdBy, ?string $notes = null): PurchaseOrder
    {
        DB::beginTransaction();

        try {
            // Group items by supplier
            $itemsBySupplier = collect($items)->groupBy('supplier_id');

            if ($itemsBySupplier->count() > 1) {
                throw new Exception('All items must be from the same supplier');
            }

            $supplierId = $itemsBySupplier->keys()->first();

            $purchaseOrder = PurchaseOrder::query()->create([
                'supplier_id' => $supplierId,
                'store_id' => $storeId,
                'created_by' => $createdBy,
                'status' => 'draft',
                'notes' => $notes,
                'expected_delivery_at' => now()->addDays($this->getAverageLeadTime($supplierId)),
            ]);

            $totalAmount = 0;

            foreach ($items as $item) {
                $product = Product::query()->find($item['product_id']);
                $quantity = $item['quantity'];
                $unitCost = $product->cost;
                $totalCost = $quantity * $unitCost;

                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $product->id,
                    'quantity_ordered' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'notes' => $item['notes'] ?? null,
                ]);

                $totalAmount += $totalCost;
            }

            $purchaseOrder->update(['total_amount' => $totalAmount]);

            DB::commit();

            $this->clearReorderCache($storeId);

            return $purchaseOrder;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get automatic reorder suggestions (products that should be reordered now)
     */
    public function getAutomaticReorderSuggestions(int $storeId): Collection
    {
        return $this->remember("auto_$storeId", function () use ($storeId) {
            $reorderList = $this->getReorderList($storeId);

            return $reorderList->filter(fn (array $item): bool =>
                // Auto-reorder if:
                // 1. High priority (severity >= 4)
                // 2. Less than 3 days of stock remaining
                // 3. No pending orders for this product
                $item['priority'] >= 4
                && $item['days_remaining'] <= 3
                && $item['pending_orders'] === 0)->values();
        }, 180, ["store_$storeId"]);
    }

    /**
     * Generate supplier comparison for reordering
     */
    public function getSupplierComparison(int $storeId): Collection
    {
        return $this->remember("supplier_comparison_$storeId", function () use ($storeId) {
            $supplierGroups = $this->getReorderListBySupplier($storeId);

            return $supplierGroups->map(fn (array $group): array => [
                'supplier' => $group['supplier'],
                'items_count' => $group['total_items'],
                'total_cost' => $group['total_cost'],
                'high_priority_items' => $group['high_priority_items'],
                'avg_lead_time' => $group['avg_lead_time'],
                'last_order_date' => $this->getLastOrderDateForSupplier($group['supplier']['id']),
                'reliability_score' => $this->calculateSupplierReliabilityScore($group['supplier']['id']),
                'priority_score' => $this->calculateSupplierPriorityScore($group),
            ])->sortByDesc('priority_score')->values();
        }, 600, ["store_$storeId"]);
    }

    /**
     * Get reorder history for analytics
     */
    public function getReorderHistory(int $storeId, int $days = 30): Collection
    {
        return $this->remember("history_{$storeId}_$days", fn () => PurchaseOrder::query()
            ->with(['supplier', 'items.product'])
            ->where('store_id', $storeId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($po): array => [
                'po_number' => $po->po_number,
                'supplier' => $po->supplier->name,
                'status' => $po->status,
                'items_count' => $po->items->count(),
                'total_amount' => $po->total_amount,
                'created_at' => $po->created_at,
                'ordered_at' => $po->ordered_at,
                'received_at' => $po->received_at,
                'delivery_days' => $po->received_at ? $po->ordered_at?->diffInDays($po->received_at) : null,
            ]), 1800, ["store_$storeId", 'history']);
    }

    /**
     * Clear reorder cache for a store
     */
    public function clearReorderCache(int $storeId): void
    {
        $this->flushTags(['reorder', "store_$storeId"]);
        $this->logInfo("Cleared reorder cache for store $storeId");
    }

    /**
     * Clear all reorder cache
     */
    public function clearAllReorderCache(): void
    {
        $this->flushTag('reorder');
        $this->logInfo('Cleared all reorder cache');
    }

    /**
     * Update reorder point thresholds based on sales data
     */
    public function updateReorderPoints(int $storeId): int
    {
        return $this->inventoryAlertService->updateOptimalThresholds($storeId);
    }

    /**
     * Get reorder statistics for a store
     */
    public function getReorderStats(int $storeId): array
    {
        return $this->remember("reorder_stats_$storeId", function () use ($storeId): array {
            $reorderList = $this->getReorderList($storeId);
            $criticalItems = $this->getCriticalReorderItems($storeId);

            return [
                'total_items_to_reorder' => $reorderList->count(),
                'critical_items' => $criticalItems->count(),
                'total_estimated_cost' => $reorderList->sum('estimated_cost'),
                'unique_suppliers' => $reorderList->pluck('supplier_id')->unique()->count(),
                'out_of_stock_items' => $reorderList->where('current_stock', 0)->count(),
                'low_stock_items' => $reorderList->where('current_stock', '>', 0)->count(),
            ];
        }, 300, ['reorder', 'inventory']);
    }

    /**
     * Get critical reorder items for a store
     */
    public function getCriticalReorderItems(int $storeId): Collection
    {
        return $this->remember("critical_reorder_items_$storeId", function () use ($storeId): Collection {
            // Get low stock items from the inventory alert service
            $lowStockItems = $this->inventoryAlertService->getLowStockForStore($storeId);

            // Filter for critical items (out of stock or very low stock)
            return $lowStockItems->filter(fn (array $item): bool => $item['current_stock'] <= 0 ||
                   ($item['current_stock'] <= ($item['threshold'] * 0.25)));
        }, 300, ['reorder', 'inventory']);
    }

    /**
     * Get reorder suggestions for a store
     */
    public function getReorderSuggestions(int $storeId): Collection
    {
        return $this->remember("reorder_suggestions_$storeId", fn (): Collection =>
            // Get automatic reorder suggestions
            $this->getAutomaticReorderSuggestions($storeId), 300, ['reorder', 'inventory']);
    }

    /**
     * Get pending order quantity for a product
     */
    private function getPendingOrderQuantity(int $productId, int $storeId): int
    {
        return PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->whereIn('status', ['draft', 'pending', 'ordered']);
            })
            ->where('product_id', $productId)
            ->sum('quantity_ordered');
    }

    /**
     * Get last order date for a product
     */
    private function getLastOrderDate(int $productId): ?string
    {
        $lastOrder = PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($query): void {
                $query->where('status', '!=', 'cancelled');
            })
            ->where('product_id', $productId)
            ->latest('created_at')
            ->first();

        return $lastOrder?->created_at?->format('Y-m-d');
    }

    /**
     * Get last order date for a supplier
     */
    private function getLastOrderDateForSupplier(int $supplierId): ?string
    {
        $lastOrder = PurchaseOrder::query()
            ->where('supplier_id', $supplierId)
            ->where('status', '!=', 'cancelled')
            ->latest('created_at')
            ->first();

        return $lastOrder?->created_at?->format('Y-m-d');
    }

    /**
     * Get average lead time for a supplier
     */
    private function getAverageLeadTime(int $supplierId): int
    {
        return $this->remember("lead_time_$supplierId", function () use ($supplierId): int {
            $orders = PurchaseOrder::query()
                ->where('supplier_id', $supplierId)
                ->whereNotNull('ordered_at')
                ->whereNotNull('received_at')
                ->select(['ordered_at', 'received_at'])
                ->get();

            if ($orders->isEmpty()) {
                return 7; // Default to 7 days if no history
            }

            $totalDays = $orders->sum(fn ($order) => $order->ordered_at->diffInDays($order->received_at));

            $avgDays = $totalDays / $orders->count();

            return (int) ($avgDays ?: 7); // Default to 7 days if no history
        }, 3600, ["supplier_$supplierId"]);
    }

    /**
     * Calculate supplier reliability score (0-100)
     */
    private function calculateSupplierReliabilityScore(int $supplierId): int
    {
        return $this->remember("reliability_$supplierId", function () use ($supplierId): int {
            $orders = PurchaseOrder::query()
                ->where('supplier_id', $supplierId)
                ->whereIn('status', ['received', 'cancelled'])
                ->where('created_at', '>=', now()->subMonths(6))
                ->get();

            if ($orders->isEmpty()) {
                return 50; // Default score for new suppliers
            }

            $completed = $orders->where('status', 'received')->count();
            $total = $orders->count();

            $completionRate = ($completed / $total) * 100;

            // Factor in delivery timeliness
            $onTimeDeliveries = $orders->filter(fn ($order): bool => $order->received_at && $order->expected_delivery_at &&
                $order->received_at <= $order->expected_delivery_at)->count();

            $timelinessRate = $completed > 0 ? ($onTimeDeliveries / $completed) * 100 : 100;

            return (int) (($completionRate * 0.7) + ($timelinessRate * 0.3));
        }, 3600, ["supplier_$supplierId"]);
    }

    /**
     * Calculate supplier priority score for ordering
     */
    private function calculateSupplierPriorityScore(array $supplierGroup): float
    {
        $highPriorityWeight = $supplierGroup['high_priority_items'] * 10;
        $itemCountWeight = $supplierGroup['total_items'] * 2;
        $costWeight = min($supplierGroup['total_cost'] / 1000, 10); // Cap at 10 points
        $leadTimeWeight = max(10 - $supplierGroup['avg_lead_time'], 0); // Shorter lead time = higher score

        return $highPriorityWeight + $itemCountWeight + $costWeight + $leadTimeWeight;
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
}
