<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;

final class SaleDTO extends BaseDTO
{
    public function __construct(
        public readonly int $store_id,
        public readonly int $cashier_id,
        public readonly PaymentMethod $payment_method,
        public readonly SaleStatus $status = SaleStatus::PENDING,
        public readonly ?int $customer_id = null,
        public readonly ?string $code = null,
        public readonly int $items_count = 0,
        public readonly float $subtotal = 0.0,
        public readonly float $discount = 0.0,
        public readonly float $tax = 0.0,
        public readonly float $total = 0.0,
        public readonly ?string $paid_at = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();
        if (! $data['code']) {
            $data['code'] = $this->generateSaleCode();
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['code']); // Don't update sale code

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    private function generateSaleCode(): string
    {
        return 'TXN-'.mb_str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
