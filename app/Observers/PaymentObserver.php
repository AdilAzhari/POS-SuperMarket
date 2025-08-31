<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Payment;

final class PaymentObserver
{
    /**
     * Handle the Payment "creating" event.
     */
    public function creating(Payment $payment): void
    {
        if (empty($payment->payment_code)) {
            $payment->payment_code = $this->generatePaymentCode();
        }

        // Calculate net amount after fees
        $payment->net_amount = $payment->amount - $payment->fee;
    }

    /**
     * Generate a unique payment code.
     */
    private function generatePaymentCode(): string
    {
        $prefix = 'PAY';
        $lastRecord = Payment::query()->latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;

        return $prefix.'-'.mb_str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
