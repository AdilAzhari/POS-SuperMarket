<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

final class StockService extends BaseService
{
    protected string $cachePrefix = 'stock:';

    public function getStockForStore(int $productId, int $storeId): int
    {
        $cacheKey = "product:$productId:store:$storeId";

        return $this->remember($cacheKey, function () use ($productId, $storeId): int {
            $product = Product::query()->findOrFail($productId);
            $pivotRecord = $product->stores()->where('stores.id', $storeId)->first();

            return $pivotRecord ? (int) ($pivotRecord->pivot->stock ?? 0) : 0;
        });
    }

    public function decrementStock(int $productId, int $storeId, int $quantity): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->logInfo('Decrementing stock for product', ['product_id' => $productId, 'store_id' => $storeId, 'quantity' => $quantity]);

        // Ensure pivot record exists
        $pivotRecord = $product->stores()->where('stores.id', $storeId)->first();
        if (! $pivotRecord) {
            $product->stores()->attach($storeId, ['stock' => 0, 'low_stock_threshold' => 0]);
            $pivotRecord = $product->stores()->where('stores.id', $storeId)->first();
        }

        $currentStock = (int) ($pivotRecord->pivot->stock ?? 0);
        $newStock = max(0, $currentStock - $quantity);

        $product->stores()->updateExistingPivot($storeId, ['stock' => $newStock]);

        // Clear cache
        $this->forget("product:$productId:store:$storeId");

        $this->logInfo('Stock decremented', [
            'product_id' => $productId,
            'store_id' => $storeId,
            'quantity' => $quantity,
            'previous_stock' => $currentStock,
            'new_stock' => $newStock,
        ]);
    }

    public function incrementStock(int $productId, int $storeId, int $quantity): void
    {
        $product = Product::query()->findOrFail($productId);

        $pivotRecord = $product->stores()->where('stores.id', $storeId)->first();
        if (! $pivotRecord) {
            $product->stores()->attach($storeId, ['stock' => $quantity, 'low_stock_threshold' => 0]);
        } else {
            $currentStock = (int) ($pivotRecord->pivot->stock ?? 0);
            $newStock = $currentStock + $quantity;
            $product->stores()->updateExistingPivot($storeId, ['stock' => $newStock]);
        }

        // Clear cache
        $this->forget("product:$productId:store:$storeId");

        $this->logInfo('Stock incremented', [
            'product_id' => $productId,
            'store_id' => $storeId,
            'quantity' => $quantity,
        ]);
    }

    /**
     * Validate if stock is sufficient for the requested quantity
     */
    public function validateStockSufficiency(int $productId, int $storeId, int $requestedQuantity): array
    {
        $availableStock = $this->getStockForStore($productId, $storeId);
        $product = Product::query()->find($productId);

        $isValid = $availableStock >= $requestedQuantity;

        $result = [
            'valid' => $isValid,
            'available_stock' => $availableStock,
            'requested_quantity' => $requestedQuantity,
            'product_name' => $product?->name ?? 'Unknown Product',
        ];

        if (! $isValid) {
            $result['message'] = "Insufficient stock for product {$product?->name}. Available: {$availableStock}, Required: {$requestedQuantity}";
            $this->logWarning('Insufficient stock validation failed', $result);
        }

        return $result;
    }

    /**
     * Check if stock level is below low stock threshold
     */
    public function checkLowStock(int $productId, int $storeId): array
    {
        $product = Product::query()->find($productId);
        $pivotRecord = $product?->stores()->where('stores.id', $storeId)->first();

        $currentStock = $pivotRecord ? (int) ($pivotRecord->pivot->stock ?? 0) : 0;
        $lowStockThreshold = $pivotRecord ? (int) ($pivotRecord->pivot->low_stock_threshold ?? 5) : 5;

        $isLowStock = $currentStock <= $lowStockThreshold;

        return [
            'is_low_stock' => $isLowStock,
            'current_stock' => $currentStock,
            'low_stock_threshold' => $lowStockThreshold,
            'product_name' => $product?->name ?? 'Unknown Product',
        ];
    }

    /**
     * Get stock status summary for a product across all stores
     */
    public function getProductStockSummary(int $productId): array
    {
        $product = Product::query()->with('stores')->find($productId);

        if (! $product) {
            return ['error' => 'Product not found'];
        }

        $stores = [];
        $totalStock = 0;
        $lowStockStores = 0;

        foreach ($product->stores as $store) {
            $stock = (int) ($store->pivot->stock ?? 0);
            $threshold = (int) ($store->pivot->low_stock_threshold ?? 5);
            $isLowStock = $stock <= $threshold;

            $stores[] = [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'stock' => $stock,
                'low_stock_threshold' => $threshold,
                'is_low_stock' => $isLowStock,
            ];

            $totalStock += $stock;
            if ($isLowStock) {
                $lowStockStores++;
            }
        }

        return [
            'product_id' => $productId,
            'product_name' => $product->name,
            'total_stock' => $totalStock,
            'total_stores' => count($stores),
            'low_stock_stores' => $lowStockStores,
            'stores' => $stores,
        ];
    }

    /**
     * Get real-time stock validation for multiple products
     */
    public function validateMultipleProducts(array $items): array
    {
        $results = [];
        $hasErrors = false;

        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $storeId = $item['store_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            if (! $productId || ! $storeId || $quantity <= 0) {
                $results[] = [
                    'valid' => false,
                    'product_id' => $productId,
                    'store_id' => $storeId,
                    'message' => 'Invalid item data provided',
                ];
                $hasErrors = true;

                continue;
            }

            $validation = $this->validateStockSufficiency($productId, $storeId, $quantity);
            $results[] = array_merge($validation, [
                'product_id' => $productId,
                'store_id' => $storeId,
            ]);

            if (! $validation['valid']) {
                $hasErrors = true;
            }
        }

        return [
            'valid' => ! $hasErrors,
            'items' => $results,
            'total_items' => count($items),
            'invalid_items' => array_filter($results, fn (array $item): bool => ! $item['valid']),
        ];
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('inventory')->info("[{$this->getServiceName()}] $message", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('inventory')->error("[{$this->getServiceName()}] $message", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('inventory')->warning("[{$this->getServiceName()}] $message", $context);
    }
}
