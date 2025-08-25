<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED, self::EXPIRED => 'red',
            self::CANCELLED => 'gray',
            self::REFUNDED => 'orange',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::REFUNDED,
            self::EXPIRED,
        ]);
    }

    public function canBeRetried(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::EXPIRED,
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
