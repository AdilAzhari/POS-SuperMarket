<?php

declare(strict_types=1);

namespace App\DTOs;

final class SupplierDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $contact_person,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $address,
        public readonly bool $is_active = true,
        public readonly ?string $website = null,
        public readonly ?string $notes = null,
        public readonly ?array $payment_terms = null,
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
