<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case ORDERED = 'ordered';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
