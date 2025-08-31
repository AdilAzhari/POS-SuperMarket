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
 * @property string $batch_number
 * @property string|null $manufacture_date
 * @property string|null $expiry_date
 * @property int $quantity
 * @property string|null $cost_price
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|ProductBatch newModelQuery()
 * @method static Builder<static>|ProductBatch newQuery()
 * @method static Builder<static>|ProductBatch query()
 * @method static Builder<static>|ProductBatch whereBatchNumber($value)
 * @method static Builder<static>|ProductBatch whereCostPrice($value)
 * @method static Builder<static>|ProductBatch whereCreatedAt($value)
 * @method static Builder<static>|ProductBatch whereExpiryDate($value)
 * @method static Builder<static>|ProductBatch whereId($value)
 * @method static Builder<static>|ProductBatch whereManufactureDate($value)
 * @method static Builder<static>|ProductBatch whereNotes($value)
 * @method static Builder<static>|ProductBatch whereProductId($value)
 * @method static Builder<static>|ProductBatch whereQuantity($value)
 * @method static Builder<static>|ProductBatch whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class ProductBatch extends Model
{
    //
}
