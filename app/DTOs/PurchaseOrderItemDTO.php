<?php

declare(strict_types=1);

namespace App\DTOs;

final class PurchaseOrderItemDTO extends BaseDTO
{
    public function __construct(
        public readonly int $purchase_order_id,
        public readonly int $product_id,
        public readonly int $quantity_ordered,
        public readonly float $unit_cost,
        public readonly int $quantity_received = 0,
        public readonly ?float $total_cost = null,
        public readonly ?string $notes = null,
    ) {}

    public function toCreateArray(): array
    {
        $data = $this->toArray();

        if ($data['total_cost'] === null) {
            $data['total_cost'] = $this->quantity_ordered * $this->unit_cost;
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }

    public function toUpdateArray(): array
    {
        $data = $this->toArray();

        // Recalculate total cost if quantities or unit cost change
        if ($data['total_cost'] === null) {
            $data['total_cost'] = $this->quantity_ordered * $this->unit_cost;
        }

        return array_filter($data, fn ($value): bool => $value !== null);
    }
}
