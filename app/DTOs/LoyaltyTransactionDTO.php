<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\LoyaltyTransactionType;

final class LoyaltyTransactionDTO extends BaseDTO
{
    public function __construct(
        public readonly int $customer_id,
        public readonly LoyaltyTransactionType $type,
        public readonly int $points,
        public readonly string $description,
        public readonly ?int $sale_id = null,
        public readonly ?array $metadata = null,
        public readonly ?string $expires_at = null,
    ) {}

    public function toCreateArray(): array
    {
        return array_filter($this->toArray(), fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        return array_filter($this->toArray(), fn ($value): bool => $value !== null);
    }
}
