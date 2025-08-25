<?php

namespace App\DTOs;

use App\Models\Sale;

class SaleResponseDTO extends BaseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly int $store_id,
        public readonly ?int $customer_id,
        public readonly int $cashier_id,
        public readonly int $items_count,
        public readonly float $subtotal,
        public readonly float $discount,
        public readonly float $tax,
        public readonly float $total,
        public readonly string $payment_method,
        public readonly string $status,
        public readonly string $paid_at,
        public readonly string $created_at,
        public readonly array $items = [],
        public readonly ?array $store = null,
        public readonly ?array $customer = null,
        public readonly ?array $cashier = null,
        public readonly ?array $payment = null
    ) {}

    public static function fromModel(Sale $sale): self
    {
        return new self(
            id: $sale->id,
            code: $sale->code,
            store_id: $sale->store_id,
            customer_id: $sale->customer_id,
            cashier_id: $sale->cashier_id,
            items_count: $sale->items_count,
            subtotal: $sale->subtotal,
            discount: $sale->discount,
            tax: $sale->tax,
            total: $sale->total,
            payment_method: is_string($sale->payment_method) ? $sale->payment_method : $sale->payment_method->value,
            status: is_string($sale->status) ? $sale->status : $sale->status->value,
            paid_at: $sale->paid_at?->toISOString() ?? '',
            created_at: $sale->created_at->toISOString(),
            items: $sale->items?->map(fn ($item) => $item->toArray())->toArray() ?? [],
            store: $sale->store?->toArray(),
            customer: $sale->customer?->toArray(),
            cashier: $sale->cashier?->toArray(),
            payment: $sale->latestPayment?->toArray()
        );
    }
}
