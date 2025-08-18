<?php

namespace App\DTOs;

class SaleItemDTO extends BaseDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly string $product_name,
        public readonly string $sku,
        public readonly float $price,
        public readonly int $quantity,
        public readonly float $discount = 0.0,
        public readonly float $tax = 0.0,
        public readonly float $line_total = 0.0
    ) {
    }

    public function calculateLineTotal(): float
    {
        return ($this->price * $this->quantity) - $this->discount + $this->tax;
    }
}