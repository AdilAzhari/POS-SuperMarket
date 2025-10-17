<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\StoreFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $manager_id
 * @property bool $is_active
 * @property array<array-key, mixed>|null $operating_hours
 * @property string $timezone
 * @property-read string $contact_info
 * @property-read string $formatted_address
 * @property-read Collection $low_stock_products
 * @property-read int $total_products
 * @property-read float $total_sales_amount
 * @property-read int $total_stock
 * @property-read User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Sale> $sales
 * @property-read int|null $sales_count
 *
 * @method static Builder<static>|Store active()
 * @method static StoreFactory factory($count = null, $state = [])
 * @method static Builder<static>|Store newModelQuery()
 * @method static Builder<static>|Store newQuery()
 * @method static Builder<static>|Store query()
 * @method static Builder<static>|Store whereAddress($value)
 * @method static Builder<static>|Store whereCreatedAt($value)
 * @method static Builder<static>|Store whereEmail($value)
 * @method static Builder<static>|Store whereId($value)
 * @method static Builder<static>|Store whereIsActive($value)
 * @method static Builder<static>|Store whereManagerId($value)
 * @method static Builder<static>|Store whereName($value)
 * @method static Builder<static>|Store whereOperatingHours($value)
 * @method static Builder<static>|Store wherePhone($value)
 * @method static Builder<static>|Store whereTimezone($value)
 * @method static Builder<static>|Store whereUpdatedAt($value)
 * @method static Builder<static>|Store withContact()
 *
 * @mixin Eloquent
 */
final class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'manager_id',
        'is_active',
        'operating_hours',
        'timezone',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
        'operating_hours' => 'array',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['stock', 'low_stock_threshold'])
            ->withTimestamps();
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeWithContact(Builder $query): Builder
    {
        return $query->where(function ($q): void {
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
            $contact[] = "Phone: $this->phone";
        }
        if ($this->email) {
            $contact[] = "Email: $this->email";
        }

        return $contact === [] ? 'No contact information' : implode(' | ', $contact);
    }

    // Business logic methods
    public function updateProductStock(int $productId, int $newStock): int
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
