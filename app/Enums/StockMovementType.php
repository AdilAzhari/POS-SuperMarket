<?php

namespace App\Enums;

enum StockMovementType: string
{
    case ADDITION = 'addition';
    case REDUCTION = 'reduction';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::ADDITION => 'Stock Addition',
            self::REDUCTION => 'Stock Reduction',
            self::TRANSFER_OUT => 'Transfer Out',
            self::TRANSFER_IN => 'Transfer In',
            self::ADJUSTMENT => 'Stock Adjustment',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ADDITION => 'green',
            self::REDUCTION => 'red',
            self::TRANSFER_OUT => 'orange',
            self::TRANSFER_IN => 'blue',
            self::ADJUSTMENT => 'purple',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::ADDITION => 'plus-circle',
            self::REDUCTION => 'minus-circle',
            self::TRANSFER_OUT => 'arrow-up-right',
            self::TRANSFER_IN => 'arrow-down-left',
            self::ADJUSTMENT => 'adjustments',
        };
    }

    public function isPositive(): bool
    {
        return in_array($this, [
            self::ADDITION,
            self::TRANSFER_IN,
        ]);
    }

    public function isNegative(): bool
    {
        return in_array($this, [
            self::REDUCTION,
            self::TRANSFER_OUT,
        ]);
    }

    public function requiresSourceStore(): bool
    {
        return $this === self::TRANSFER_OUT;
    }

    public function requiresDestinationStore(): bool
    {
        return $this === self::TRANSFER_IN;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'icon' => $case->icon(),
            ])
            ->toArray();
    }
}