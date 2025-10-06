<?php

declare(strict_types=1);

namespace App\Actions\Models;

use App\Models\StockMovement;

final class GenerateStockMovementCodeAction
{
    /**
     * Generate a unique code for the stock movement
     */
    public function execute(): string
    {
        $prefix = 'SM';
        $lastRecord = StockMovement::query()->latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;

        return $prefix.'-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
