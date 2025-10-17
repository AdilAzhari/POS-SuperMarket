<?php

declare(strict_types=1);

namespace App\DTOs;

final class ProductReturnDTO extends BaseDTO
{
    public function __construct(
        public readonly int $sale_id,
        public readonly int $store_id,
        public readonly int $processed_by,
        public readonly string $reason,
        public readonly string $refund_method,
        public readonly float $subtotal,
        public readonly float $tax_refund,
        public readonly float $total_refund,
        public readonly ?int $customer_id = null,
        public readonly ?string $code = null,
        public readonly string $status = 'pending',
        public readonly ?string $notes = null,
        public readonly ?string $processed_at = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();
        if (! $data['code']) {
            $data['code'] = $this->generateReturnCode();
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['code']); // Don't update return code

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    private function generateReturnCode(): string
    {
        return 'RET-'.mb_str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
