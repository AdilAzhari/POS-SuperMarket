<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class ProcessStockTransferAction
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Process stock transfer between stores
     */
    public function execute(array $transferData, int $userId): Collection
    {
        $fromStoreId = $transferData['from_store_id'];
        $toStoreId = $transferData['to_store_id'];
        $items = $transferData['items'];
        $notes = $transferData['notes'] ?? 'Stock transfer';

        return DB::transaction(function () use ($fromStoreId, $toStoreId, $items, $notes, $userId): Collection {
            $movements = collect();

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $itemNotes = $item['notes'] ?? $notes;

                // Record outbound movement from source store
                $outboundMovement = $this->stockService->recordStockMovement([
                    'product_id' => $productId,
                    'store_id' => $fromStoreId,
                    'type' => StockMovementType::TRANSFER_OUT,
                    'reason' => StockMovementReason::TRANSFER_OUT,
                    'quantity' => $quantity,
                    'from_store_id' => $fromStoreId,
                    'to_store_id' => $toStoreId,
                    'notes' => "Transfer to store ID: $toStoreId - $itemNotes",
                    'user_id' => $userId,
                ]);

                // Record inbound movement to destination store
                $inboundMovement = $this->stockService->recordStockMovement([
                    'product_id' => $productId,
                    'store_id' => $toStoreId,
                    'type' => StockMovementType::TRANSFER_IN,
                    'reason' => StockMovementReason::TRANSFER_IN,
                    'quantity' => $quantity,
                    'from_store_id' => $fromStoreId,
                    'to_store_id' => $toStoreId,
                    'notes' => "Transfer from store ID: $fromStoreId - $itemNotes",
                    'user_id' => $userId,
                ]);

                $movements->push($outboundMovement, $inboundMovement);

                Log::info('Stock transfer processed', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'from_store_id' => $fromStoreId,
                    'to_store_id' => $toStoreId,
                    'outbound_movement_id' => $outboundMovement->id,
                    'inbound_movement_id' => $inboundMovement->id,
                    'user_id' => $userId,
                ]);
            }

            return $movements;
        });
    }
}
