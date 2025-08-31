<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;

final class PaymentDTO extends BaseDTO
{
    public function __construct(
        public readonly int $sale_id,
        public readonly int $store_id,
        public readonly int $user_id,
        public readonly PaymentMethod $payment_method,
        public readonly float $amount,
        public readonly PaymentStatus $status = PaymentStatus::PENDING,
        public readonly ?string $payment_code = null,
        public readonly float $fee = 0.0,
        public readonly ?float $net_amount = null,
        public readonly string $currency = 'MYR',
        public readonly ?string $gateway_transaction_id = null,
        public readonly ?string $gateway_reference = null,
        public readonly ?array $gateway_response = null,
        public readonly ?string $card_last_four = null,
        public readonly ?string $card_brand = null,
        public readonly ?string $card_exp_month = null,
        public readonly ?string $card_exp_year = null,
        public readonly ?string $tng_phone = null,
        public readonly ?string $tng_reference = null,
        public readonly ?float $cash_received = null,
        public readonly ?float $change_amount = null,
        public readonly ?string $notes = null,
        public readonly ?string $processed_at = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();

        if (! $data['payment_code']) {
            $data['payment_code'] = $this->generatePaymentCode();
        }

        if ($data['net_amount'] === null) {
            $data['net_amount'] = $this->amount - $this->fee;
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['payment_code']); // Don't update payment code

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    private function generatePaymentCode(): string
    {
        return 'PAY-'.mb_str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
