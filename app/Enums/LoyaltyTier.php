<?php

declare(strict_types=1);

namespace App\Enums;

enum LoyaltyTier: string
{
    case BRONZE = 'bronze';
    case SILVER = 'silver';
    case GOLD = 'gold';
    case PLATINUM = 'platinum';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
