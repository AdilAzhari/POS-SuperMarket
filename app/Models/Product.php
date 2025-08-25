<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
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
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('sku', 'LIKE', "%$search%")
                ->orWhere('barcode', 'LIKE', "%$search%")
                ->orWhereHas('category', fn ($query) => $query->where('name', 'LIKE', "%$search%"));
        });
    }

    public function scopeLowStock($query, $storeId)
    {
        return $query->whereHas('stores', function ($q) use ($storeId) {
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
