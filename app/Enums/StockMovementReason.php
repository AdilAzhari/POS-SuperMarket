<?php

declare(strict_types=1);

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

    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($case): array => [
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
            ->filter(fn ($case): bool => $case->category() === $category)
            ->map(fn ($case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
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
            self::TRANSFER_OUT => 'Transfer Out',
            self::TRANSFER_IN => 'Transfer In',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PURCHASE => 'green',
            self::SALE => 'blue',
            self::RETURN => 'yellow',
            self::DAMAGED, self::THEFT => 'red',
            self::EXPIRED => 'orange',
            self::TRANSFER, self::TRANSFER_OUT, self::TRANSFER_IN => 'purple',
            self::RECOUNT => 'indigo',
            self::LOST => 'gray',
            self::PROMOTIONAL => 'pink',
            self::SAMPLE => 'teal',
            self::WASTE => 'slate',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::PURCHASE, self::RETURN, self::TRANSFER_IN => 'inbound',
            self::SALE, self::TRANSFER, self::TRANSFER_OUT => 'outbound',
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
}
