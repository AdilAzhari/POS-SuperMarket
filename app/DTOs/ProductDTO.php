<?php

declare(strict_types=1);

namespace App\DTOs;

final class ProductDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly float $price,
        public readonly float $cost,
        public readonly int $category_id,
        public readonly ?int $supplier_id = null,
        public readonly ?string $barcode = null,
        public readonly ?string $internal_code = null,
        public readonly ?string $description = null,
        public readonly bool $active = true,
        public readonly bool $track_serial_numbers = false,
        public readonly bool $track_batches = false,
        public readonly int $reorder_point = 0,
        public readonly int $reorder_quantity = 0,
        public readonly ?int $max_stock_level = null,
        public readonly ?float $weight = null,
        public readonly string $weight_unit = 'kg',
        public readonly ?float $length = null,
        public readonly ?float $width = null,
        public readonly ?float $height = null,
        public readonly string $dimension_unit = 'cm',
        public readonly bool $is_featured = false,
        public readonly bool $allow_backorder = false,
        public readonly ?string $discontinued_at = null,
        public readonly ?float $wholesale_price = null,
        public readonly ?float $compare_at_price = null,
        public readonly bool $tax_exempt = false,
        public readonly ?string $manufacturer = null,
        public readonly ?string $brand = null,
        public readonly ?string $notes = null,
        public readonly ?array $tags = null,
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
