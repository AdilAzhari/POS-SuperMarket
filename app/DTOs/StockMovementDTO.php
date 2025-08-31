<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;

final class StockMovementDTO extends BaseDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly int $store_id,
        public readonly int $user_id,
        public readonly StockMovementType $type,
        public readonly int $quantity,
        public readonly StockMovementReason $reason,
        public readonly ?string $code = null,
        public readonly ?string $notes = null,
        public readonly ?int $from_store_id = null,
        public readonly ?int $to_store_id = null,
        public readonly ?string $occurred_at = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();

        if (! $data['code']) {
            $data['code'] = $this->generateMovementCode();
        }

        if (! $data['occurred_at']) {
            $data['occurred_at'] = now()->toDateTimeString();
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        unset($data['code']); // Don't update movement code

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    private function generateMovementCode(): string
    {
        $prefix = match ($this->type) {
            StockMovementType::ADDITION => 'ADD',
            StockMovementType::REDUCTION => 'RED',
            StockMovementType::TRANSFER_OUT => 'OUT',
            StockMovementType::TRANSFER_IN => 'IN',
            StockMovementType::ADJUSTMENT => 'ADJ',
            default => 'MOV',
        };

        return $prefix.'-'.mb_str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
