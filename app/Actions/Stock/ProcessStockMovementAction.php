<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcessStockMovementAction
{
    /**
     * Process single stock movement with stock adjustment
     */
    public function execute(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            // Generate movement code if not provided
            if (! isset($data['code'])) {
                $data['code'] = 'STK-'.date('ymdHis').'-'.random_int(100, 999);
            }

            if (! isset($data['occurred_at'])) {
                $data['occurred_at'] = now();
            }

            $movement = StockMovement::create($data);

            // Adjust pivot stock for product-store
            $this->adjustProductStock($data);

            Log::info('[StockMovement] Created stock movement', [
                'movement_id' => $movement->id,
                'code' => $movement->code,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
            ]);

            return $movement->load(['product', 'store', 'user']);
        });
    }

    private function adjustProductStock(array $data): void
    {
        $product = Product::findOrFail($data['product_id']);
        $store = Store::findOrFail($data['store_id']);

        // Ensure product-store relationship exists
        $pivot = $product->stores()->where('stores.id', $store->id)->first();
        if (! $pivot) {
            $product->stores()->attach($store->id, ['stock' => 0, 'low_stock_threshold' => 0]);
        }

        $currentStock = (int) $product->stores()->where('stores.id', $store->id)->first()->pivot->stock;
        $movementType = StockMovementType::from($data['type']);
        $delta = $movementType->isPositive() ? $data['quantity'] : -$data['quantity'];
        $newStock = max(0, $currentStock + $delta);

        $product->stores()->updateExistingPivot($store->id, ['stock' => $newStock]);

        Log::info('[StockMovement] Adjusted stock', [
            'product_id' => $product->id,
            'store_id' => $store->id,
            'previous_stock' => $currentStock,
            'delta' => $delta,
            'new_stock' => $newStock,
        ]);
    }
}
