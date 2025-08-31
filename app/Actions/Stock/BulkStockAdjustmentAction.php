<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Services\StockService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class BulkStockAdjustmentAction
{
    public function __construct(
        private StockService $stockService
    ) {}

    /**
     * Process multiple stock adjustments in a single transaction
     */
    public function execute(array $movements, int $userId): Collection
    {
        return DB::transaction(function () use ($movements, $userId) {
            $processedMovements = collect();

            foreach ($movements as $movementData) {
                try {
                    $movement = $this->stockService->recordStockMovement([
                        'product_id' => $movementData['product_id'],
                        'store_id' => $movementData['store_id'],
                        'type' => StockMovementType::from($movementData['type']),
                        'reason' => StockMovementReason::from($movementData['reason']),
                        'quantity' => $movementData['quantity'],
                        'notes' => $movementData['notes'] ?? null,
                        'user_id' => $userId,
                        'occurred_at' => $movementData['occurred_at'] ?? now(),
                    ]);

                    $processedMovements->push($movement);

                    Log::info('Stock movement processed', [
                        'movement_id' => $movement->id,
                        'product_id' => $movement->product_id,
                        'quantity' => $movement->quantity,
                        'type' => $movement->type->value,
                        'user_id' => $userId,
                    ]);
                } catch (Exception $e) {
                    Log::error('Failed to process stock movement', [
                        'movement_data' => $movementData,
                        'error' => $e->getMessage(),
                        'user_id' => $userId,
                    ]);
                    throw $e;
                }
            }

            return $processedMovements;
        });
    }
}
