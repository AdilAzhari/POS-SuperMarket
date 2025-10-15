<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReturnItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $return_id
 * @property int $sale_item_id
 * @property int $product_id
 * @property string $product_name
 * @property string $sku
 * @property numeric $price
 * @property int $quantity_returned
 * @property int $original_quantity
 * @property numeric $line_total
 * @property string|null $condition_notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ProductReturn $productReturn
 * @property-read SaleItem $saleItem
 * @property-read Product $product
 *
 * @method static ReturnItemFactory factory($count = null, $state = [])
 * @method static Builder<static>|ReturnItem newModelQuery()
 * @method static Builder<static>|ReturnItem newQuery()
 * @method static Builder<static>|ReturnItem query()
 * @method static Builder<static>|ReturnItem whereConditionNotes($value)
 * @method static Builder<static>|ReturnItem whereCreatedAt($value)
 * @method static Builder<static>|ReturnItem whereId($value)
 * @method static Builder<static>|ReturnItem whereLineTotal($value)
 * @method static Builder<static>|ReturnItem whereOriginalQuantity($value)
 * @method static Builder<static>|ReturnItem wherePrice($value)
 * @method static Builder<static>|ReturnItem whereProductId($value)
 * @method static Builder<static>|ReturnItem whereProductName($value)
 * @method static Builder<static>|ReturnItem whereQuantityReturned($value)
 * @method static Builder<static>|ReturnItem whereReturnId($value)
 * @method static Builder<static>|ReturnItem whereSaleItemId($value)
 * @method static Builder<static>|ReturnItem whereSku($value)
 * @method static Builder<static>|ReturnItem whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class ReturnItem extends Model
{
    /** @use HasFactory<ReturnItemFactory> */
    use HasFactory;

    protected $fillable = [
        'return_id',
        'sale_item_id',
        'product_id',
        'product_name',
        'sku',
        'price',
        'quantity_returned',
        'original_quantity',
        'line_total',
        'condition_notes',
    ];

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity_returned' => 'integer',
            'original_quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }
}
