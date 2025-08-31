<?php

declare(strict_types=1);

namespace App\Enums;

enum LoyaltyRewardType: string
{
    case PERCENTAGE_DISCOUNT = 'percentage_discount';
    case FIXED_DISCOUNT = 'fixed_discount';
    case FREE_PRODUCT = 'free_product';
    case FREE_SHIPPING = 'free_shipping';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
