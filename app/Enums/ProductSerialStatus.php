<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductSerialStatus: string
{
    case AVAILABLE = 'available';
    case SOLD = 'sold';
    case RESERVED = 'reserved';
    case DEFECTIVE = 'defective';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
