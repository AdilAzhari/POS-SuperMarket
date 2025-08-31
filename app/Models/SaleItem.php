<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SaleItemFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property string $product_name
 * @property string $sku
 * @property numeric $price
 * @property int $quantity
 * @property numeric $discount
 * @property numeric $tax
 * @property numeric $line_total
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Product $product
 * @property-read Sale $sale
 *
 * @method static SaleItemFactory factory($count = null, $state = [])
 * @method static Builder<static>|SaleItem newModelQuery()
 * @method static Builder<static>|SaleItem newQuery()
 * @method static Builder<static>|SaleItem query()
 * @method static Builder<static>|SaleItem whereCreatedAt($value)
 * @method static Builder<static>|SaleItem whereDiscount($value)
 * @method static Builder<static>|SaleItem whereId($value)
 * @method static Builder<static>|SaleItem whereLineTotal($value)
 * @method static Builder<static>|SaleItem wherePrice($value)
 * @method static Builder<static>|SaleItem whereProductId($value)
 * @method static Builder<static>|SaleItem whereProductName($value)
 * @method static Builder<static>|SaleItem whereQuantity($value)
 * @method static Builder<static>|SaleItem whereSaleId($value)
 * @method static Builder<static>|SaleItem whereSku($value)
 * @method static Builder<static>|SaleItem whereTax($value)
 * @method static Builder<static>|SaleItem whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'sku',
        'price',
        'quantity',
        'discount',
        'tax',
        'line_total',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
