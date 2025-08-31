<?php

declare(strict_types=1);

namespace App\DTOs;

final class CategoryDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?int $parent_id = null,
        public readonly bool $is_active = true,
        public readonly ?string $image_url = null,
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
