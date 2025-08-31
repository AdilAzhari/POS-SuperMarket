<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property string $serial_number
 * @property string $status
 * @property int|null $sale_item_id
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|ProductSerialNumber newModelQuery()
 * @method static Builder<static>|ProductSerialNumber newQuery()
 * @method static Builder<static>|ProductSerialNumber query()
 * @method static Builder<static>|ProductSerialNumber whereCreatedAt($value)
 * @method static Builder<static>|ProductSerialNumber whereId($value)
 * @method static Builder<static>|ProductSerialNumber whereNotes($value)
 * @method static Builder<static>|ProductSerialNumber whereProductId($value)
 * @method static Builder<static>|ProductSerialNumber whereSaleItemId($value)
 * @method static Builder<static>|ProductSerialNumber whereSerialNumber($value)
 * @method static Builder<static>|ProductSerialNumber whereStatus($value)
 * @method static Builder<static>|ProductSerialNumber whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class ProductSerialNumber extends Model
{
    //
}
