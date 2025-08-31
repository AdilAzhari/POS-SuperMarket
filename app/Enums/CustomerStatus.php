<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
