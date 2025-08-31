<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $category_id
 * @property int|null $supplier_id
 * @property string $name
 * @property string $sku
 * @property string|null $barcode
 * @property string|null $internal_code
 * @property numeric $price
 * @property string|null $wholesale_price
 * @property string|null $compare_at_price
 * @property int $tax_exempt
 * @property string|null $manufacturer
 * @property string|null $brand
 * @property string|null $notes
 * @property string|null $tags
 * @property numeric $cost
 * @property string|null $description
 * @property int $track_serial_numbers
 * @property int $track_batches
 * @property int $reorder_point
 * @property int $reorder_quantity
 * @property int|null $max_stock_level
 * @property string|null $weight
 * @property string $weight_unit
 * @property string|null $length
 * @property string|null $width
 * @property string|null $height
 * @property string $dimension_unit
 * @property bool $active
 * @property int $is_featured
 * @property int $allow_backorder
 * @property string|null $discontinued_at
 * @property int $low_stock_threshold
 * @property string|null $image_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Category|null $category
 * @property-read Collection<int, SaleItem> $saleItems
 * @property-read int|null $sale_items_count
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Collection<int, Store> $stores
 * @property-read int|null $stores_count
 * @property-read Supplier|null $supplier
 *
 * @method static Builder<static>|Product active()
 * @method static Builder<static>|Product bySupplier($supplierId)
 * @method static ProductFactory factory($count = null, $state = [])
 * @method static Builder<static>|Product inCategory($categoryId)
 * @method static Builder<static>|Product lowStock($storeId)
 * @method static Builder<static>|Product newModelQuery()
 * @method static Builder<static>|Product newQuery()
 * @method static Builder<static>|Product query()
 * @method static Builder<static>|Product search($search)
 * @method static Builder<static>|Product whereActive($value)
 * @method static Builder<static>|Product whereAllowBackorder($value)
 * @method static Builder<static>|Product whereBarcode($value)
 * @method static Builder<static>|Product whereBrand($value)
 * @method static Builder<static>|Product whereCategoryId($value)
 * @method static Builder<static>|Product whereCompareAtPrice($value)
 * @method static Builder<static>|Product whereCost($value)
 * @method static Builder<static>|Product whereCreatedAt($value)
 * @method static Builder<static>|Product whereDescription($value)
 * @method static Builder<static>|Product whereDimensionUnit($value)
 * @method static Builder<static>|Product whereDiscontinuedAt($value)
 * @method static Builder<static>|Product whereHeight($value)
 * @method static Builder<static>|Product whereId($value)
 * @method static Builder<static>|Product whereImageUrl($value)
 * @method static Builder<static>|Product whereInternalCode($value)
 * @method static Builder<static>|Product whereIsFeatured($value)
 * @method static Builder<static>|Product whereLength($value)
 * @method static Builder<static>|Product whereLowStockThreshold($value)
 * @method static Builder<static>|Product whereManufacturer($value)
 * @method static Builder<static>|Product whereMaxStockLevel($value)
 * @method static Builder<static>|Product whereName($value)
 * @method static Builder<static>|Product whereNotes($value)
 * @method static Builder<static>|Product wherePrice($value)
 * @method static Builder<static>|Product whereReorderPoint($value)
 * @method static Builder<static>|Product whereReorderQuantity($value)
 * @method static Builder<static>|Product whereSku($value)
 * @method static Builder<static>|Product whereSupplierId($value)
 * @method static Builder<static>|Product whereTags($value)
 * @method static Builder<static>|Product whereTaxExempt($value)
 * @method static Builder<static>|Product whereTrackBatches($value)
 * @method static Builder<static>|Product whereTrackSerialNumbers($value)
 * @method static Builder<static>|Product whereUpdatedAt($value)
 * @method static Builder<static>|Product whereWeight($value)
 * @method static Builder<static>|Product whereWeightUnit($value)
 * @method static Builder<static>|Product whereWholesalePrice($value)
 * @method static Builder<static>|Product whereWidth($value)
 *
 * @mixin Eloquent
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'price',
        'cost',
        'active',
        'low_stock_threshold',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'active' => 'boolean',
        'low_stock_threshold' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot(['stock', 'low_stock_threshold'])
            ->withTimestamps();
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search): void {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('sku', 'LIKE', "%$search%")
                ->orWhere('barcode', 'LIKE', "%$search%")
                ->orWhereHas('category', fn ($query) => $query->where('name', 'LIKE', "%$search%"));
        });
    }

    public function scopeLowStock($query, $storeId)
    {
        return $query->whereHas('stores', function ($q) use ($storeId): void {
            $q->where('store_id', $storeId)
                ->whereRaw('product_store.stock <= product_store.low_stock_threshold');
        });
    }

    // Helper methods
    public function getStockForStore(int $storeId): int
    {
        $store = $this->stores()->where('stores.id', $storeId)->first();

        return (int) $store?->pivot->stock;
    }

    public function isLowStockForStore(int $storeId): bool
    {
        $store = $this->stores()->where('stores.id', $storeId)->first();
        if (! $store) {
            return false;
        }

        return $store->pivot->stock <= $store->pivot->low_stock_threshold;
    }
}
