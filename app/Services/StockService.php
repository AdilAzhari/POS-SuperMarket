<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Log;

class StockService extends BaseService
{
    protected string $cachePrefix = 'stock:';

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

    public function getStockForStore(int $productId, int $storeId): int
    {
        $cacheKey = "product:$productId:store:$storeId";

        return $this->remember($cacheKey, function () use ($productId, $storeId) {
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
}
