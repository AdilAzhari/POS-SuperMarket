<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyReward extends Model
{
    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'discount_value',
        'free_product_id',
        'is_active',
        'valid_from',
        'valid_until',
        'usage_limit',
        'times_used',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'discount_value' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'usage_limit' => 'integer',
        'times_used' => 'integer',
    ];

    // Relationships
    public function freeProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'free_product_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereRaw('times_used < usage_limit');
            });
    }

    // Helper methods
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isAfter(now())) {
            return false;
        }
        if ($this->valid_until && $this->valid_until->isBefore(now())) {
            return false;
        }

        if ($this->usage_limit && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeRedeemedBy(Customer $customer): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return $customer->loyalty_points >= $this->points_required;
    }

    public function getDiscountAmount(float $orderTotal = 0): float
    {
        return match ($this->type) {
            'percentage_discount' => $orderTotal * ($this->discount_value / 100),
            'fixed_discount' => $this->discount_value,
            'free_product' => $this->freeProduct?->price ?? 0,
            'free_shipping' => 0, // Implement shipping logic as needed
            default => 0
        };
    }

    public function incrementUsage(): void
    {
        $this->increment('times_used');
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'percentage_discount' => 'Percentage Discount',
            'fixed_discount' => 'Fixed Amount Discount',
            'free_product' => 'Free Product',
            'free_shipping' => 'Free Shipping',
            default => ucwords(str_replace('_', ' ', $this->type))
        };
    }

    public function getValueDisplayAttribute(): string
    {
        return match ($this->type) {
            'percentage_discount' => $this->discount_value.'%',
            'fixed_discount' => '$'.number_format($this->discount_value, 2),
            'free_product' => $this->freeProduct?->name ?? 'Product not found',
            'free_shipping' => 'Free Shipping',
            default => ''
        };
    }
}
