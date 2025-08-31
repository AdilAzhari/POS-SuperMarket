<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case DIGITAL = 'digital';
    case BANK_TRANSFER = 'bank_transfer';
    case TOUCHNGO = 'tng';
    case GRAB_PAY = 'grab_pay';
    case MOBILE_PAYMENT = 'mobile_payment';

    public static function options(): array
    {
        return collect(self::cases())
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
            self::CASH => 'Cash',
            self::CARD => 'Credit/Debit Card',
            self::DIGITAL => 'Digital Payment',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::TOUCHNGO => 'Touch \'n Go',
            self::GRAB_PAY => 'GrabPay',
            self::MOBILE_PAYMENT => 'Mobile Payment',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CASH => 'green',
            self::CARD => 'blue',
            self::DIGITAL => 'purple',
            self::BANK_TRANSFER => 'orange',
            self::TOUCHNGO => 'red',
            self::GRAB_PAY => 'emerald',
            self::MOBILE_PAYMENT => 'indigo',
        };
    }

    public function requiresGateway(): bool
    {
        return match ($this) {
            self::CASH => false,
            default => true,
        };
    }

    public function hasProcessingFee(): bool
    {
        return match ($this) {
            self::CASH => false,
            default => true,
        };
    }
}
