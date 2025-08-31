<?php

declare(strict_types=1);

namespace App\DTOs;

final class StoreDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?int $manager_id = null,
        public readonly bool $is_active = true,
        public readonly ?array $operating_hours = null,
        public readonly ?string $timezone = null,
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
