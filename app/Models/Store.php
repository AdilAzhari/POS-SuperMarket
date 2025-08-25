<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['stock', 'low_stock_threshold'])
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('name');
    }

    public function scopeWithContact(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNotNull('phone')->orWhereNotNull('email');
        });
    }

    // Helper methods
    public function getTotalProductsAttribute(): int
    {
        return $this->products()->count();
    }

    public function getTotalStockAttribute(): int
    {
        return $this->products()->sum('stock');
    }

    public function getLowStockProductsAttribute(): Collection
    {
        return $this->products()
            ->wherePivot('stock', '<=', 'low_stock_threshold')
            ->get();
    }

    public function getTotalSalesAmountAttribute(): float
    {
        return $this->sales()->sum('total') ?? 0;
    }

    public function hasContact(): bool
    {
        return ! empty($this->phone) || ! empty($this->email);
    }

    public function getFormattedAddressAttribute(): string
    {
        return $this->address ?? 'No address provided';
    }

    public function getContactInfoAttribute(): string
    {
        $contact = [];
        if ($this->phone) {
            $contact[] = "Phone: {$this->phone}";
        }
        if ($this->email) {
            $contact[] = "Email: {$this->email}";
        }

        return empty($contact) ? 'No contact information' : implode(' | ', $contact);
    }

    // Business logic methods
    public function updateProductStock(int $productId, int $newStock): bool
    {
        return $this->products()->updateExistingPivot($productId, ['stock' => $newStock]);
    }

    public function addProduct(int $productId, int $stock = 0, int $lowStockThreshold = 5): void
    {
        $this->products()->attach($productId, [
            'stock' => $stock,
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }

    public function removeProduct(int $productId): void
    {
        $this->products()->detach($productId);
    }
}
