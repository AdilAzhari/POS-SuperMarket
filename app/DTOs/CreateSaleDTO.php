<?php

namespace App\DTOs;

class CreateSaleDTO extends BaseDTO
{
    public function __construct(
        public readonly int $store_id,
        public readonly int $cashier_id,
        public readonly ?int $customer_id,
        public readonly array $items,
        public readonly string $payment_method,
        public readonly float $discount = 0.0,
        public readonly float $tax = 0.0,
        public readonly ?string $paid_at = null
    ) {
    }

    public function getSaleItems(): array
    {
        return array_map(
            fn(array $item) => new SaleItemDTO(
                product_id: $item['product_id'],
                product_name: $item['product_name'] ?? '',
                sku: $item['sku'] ?? '',
                price: (float) $item['price'],
                quantity: (int) $item['quantity'],
                discount: (float) ($item['discount'] ?? 0),
                tax: (float) ($item['tax'] ?? 0)
            ),
            $this->items
        );
    }
}