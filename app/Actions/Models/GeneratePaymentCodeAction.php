<?php

declare(strict_types=1);

namespace App\Actions\Models;

use App\Models\Payment;

final class GeneratePaymentCodeAction
{
    /**
     * Generate a unique payment code
     */
    public function execute(): string
    {
        $prefix = 'PAY';
        $lastRecord = Payment::query()->latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;

        return $prefix.'-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
