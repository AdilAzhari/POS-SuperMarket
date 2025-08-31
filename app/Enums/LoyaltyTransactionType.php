<?php

declare(strict_types=1);

namespace App\Enums;

enum LoyaltyTransactionType: string
{
    case EARNED = 'earned';
    case REDEEMED = 'redeemed';
    case EXPIRED = 'expired';
    case ADJUSTMENT = 'adjustment';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
