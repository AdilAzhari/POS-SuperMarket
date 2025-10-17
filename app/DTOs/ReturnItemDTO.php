<?php

declare(strict_types=1);

namespace App\DTOs;

final class ReturnItemDTO extends BaseDTO
{
    public function __construct(
        public readonly int $return_id,
        public readonly int $sale_item_id,
        public readonly int $product_id,
        public readonly string $product_name,
        public readonly string $sku,
        public readonly float $price,
        public readonly int $quantity_returned,
        public readonly int $original_quantity,
        public readonly float $line_total,
        public readonly ?string $condition_notes = null,
    ) {}

    public function toCreateArray(): array
    {
        return array_filter($this->toArray(), fn ($value): bool => $value !== null);
    }
}
