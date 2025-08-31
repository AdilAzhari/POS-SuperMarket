<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\LoyaltyRewardType;

final class LoyaltyRewardDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $points_required,
        public readonly LoyaltyRewardType $type,
        public readonly ?float $discount_value = null,
        public readonly ?int $free_product_id = null,
        public readonly bool $is_active = true,
        public readonly ?string $valid_from = null,
        public readonly ?string $valid_until = null,
        public readonly ?int $usage_limit = null,
        public readonly int $times_used = 0,
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
