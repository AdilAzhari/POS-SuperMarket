<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CustomerStatus;
use App\Enums\LoyaltyTier;

final class CustomerDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?string $address = null,
        public readonly CustomerStatus $status = CustomerStatus::ACTIVE,
        public readonly int $loyalty_points = 0,
        public readonly LoyaltyTier $loyalty_tier = LoyaltyTier::BRONZE,
        public readonly ?string $birthday = null,
        public readonly bool $marketing_consent = false,
        public readonly int $total_purchases = 0,
        public readonly float $total_spent = 0.0,
        public readonly ?string $last_purchase_at = null,
    ) {}

    public function toCreateArray(): array
    {
        return array_filter($this->toArray(), fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();
        // Don't update calculated fields through DTO
        unset($data['total_purchases'], $data['total_spent'], $data['last_purchase_at']);

        return array_filter($data, fn ($value): bool => $value !== null);
    }
}
