<?php

namespace App\Enums;

enum SaleStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case VOIDED = 'voided';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
            self::VOIDED => 'Voided',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::COMPLETED => 'green',
            self::REFUNDED => 'orange',
            self::PARTIALLY_REFUNDED => 'amber',
            self::VOIDED, self::CANCELLED => 'red',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING,
            self::COMPLETED,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canBeModified(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING,
        ]);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::PARTIALLY_REFUNDED,
        ]);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ])
            ->toArray();
    }
}
