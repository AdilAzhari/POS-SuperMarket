<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case PURCHASE = 'purchase';
    case SALE = 'sale';
    case RETURN = 'return';
    case DAMAGED = 'damaged';
    case EXPIRED = 'expired';
    case TRANSFER = 'transfer';
    case RECOUNT = 'recount';
    case THEFT = 'theft';
    case LOST = 'lost';
    case PROMOTIONAL = 'promotional';
    case SAMPLE = 'sample';
    case WASTE = 'waste';

    public function label(): string
    {
        return match($this) {
            self::PURCHASE => 'Purchase from Supplier',
            self::SALE => 'Sold to Customer',
            self::RETURN => 'Customer Return',
            self::DAMAGED => 'Damaged Goods',
            self::EXPIRED => 'Expired Items',
            self::TRANSFER => 'Inter-Store Transfer',
            self::RECOUNT => 'Inventory Recount',
            self::THEFT => 'Theft/Shrinkage',
            self::LOST => 'Lost Items',
            self::PROMOTIONAL => 'Promotional Giveaway',
            self::SAMPLE => 'Free Sample',
            self::WASTE => 'Waste/Disposal',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PURCHASE => 'green',
            self::SALE => 'blue',
            self::RETURN => 'yellow',
            self::DAMAGED => 'red',
            self::EXPIRED => 'orange',
            self::TRANSFER => 'purple',
            self::RECOUNT => 'indigo',
            self::THEFT => 'red',
            self::LOST => 'gray',
            self::PROMOTIONAL => 'pink',
            self::SAMPLE => 'teal',
            self::WASTE => 'slate',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::PURCHASE, self::RETURN => 'inbound',
            self::SALE, self::TRANSFER => 'outbound',
            self::DAMAGED, self::EXPIRED, self::THEFT, self::LOST, self::WASTE => 'loss',
            self::RECOUNT => 'adjustment',
            self::PROMOTIONAL, self::SAMPLE => 'marketing',
        };
    }

    public function requiresApproval(): bool
    {
        return in_array($this, [
            self::THEFT,
            self::LOST,
            self::RECOUNT,
        ]);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'category' => $case->category(),
            ])
            ->toArray();
    }

    public static function byCategory(string $category): array
    {
        return collect(self::cases())
            ->filter(fn($case) => $case->category() === $category)
            ->map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ])
            ->toArray();
    }
}