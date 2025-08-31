<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\StockMovement;

final class StockMovementObserver
{
    /**
     * Handle the StockMovement "creating" event.
     */
    public function creating(StockMovement $stockMovement): void
    {
        if (empty($stockMovement->code)) {
            $stockMovement->code = $this->generateCode();
        }
    }

    /**
     * Generate a unique code for the stock movement.
     */
    private function generateCode(): string
    {
        $prefix = 'SM';
        $lastRecord = StockMovement::query()->latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;

        return $prefix.'-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
