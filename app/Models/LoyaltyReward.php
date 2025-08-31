<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LoyaltyRewardType;
use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $points_required
 * @property LoyaltyRewardType $type
 * @property numeric|null $discount_value
 * @property int|null $free_product_id
 * @property bool $is_active
 * @property CarbonImmutable|null $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property int|null $usage_limit
 * @property int $times_used
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Product|null $freeProduct
 * @property-read string $type_display
 * @property-read string $value_display
 * @property-read Collection<int, RewardRedemption> $redemptions
 * @property-read int|null $redemptions_count
 *
 * @method static Builder<static>|LoyaltyReward active()
 * @method static Builder<static>|LoyaltyReward available()
 * @method static Builder<static>|LoyaltyReward newModelQuery()
 * @method static Builder<static>|LoyaltyReward newQuery()
 * @method static Builder<static>|LoyaltyReward query()
 * @method static Builder<static>|LoyaltyReward whereCreatedAt($value)
 * @method static Builder<static>|LoyaltyReward whereDescription($value)
 * @method static Builder<static>|LoyaltyReward whereDiscountValue($value)
 * @method static Builder<static>|LoyaltyReward whereFreeProductId($value)
 * @method static Builder<static>|LoyaltyReward whereId($value)
 * @method static Builder<static>|LoyaltyReward whereIsActive($value)
 * @method static Builder<static>|LoyaltyReward whereName($value)
 * @method static Builder<static>|LoyaltyReward wherePointsRequired($value)
 * @method static Builder<static>|LoyaltyReward whereTimesUsed($value)
 * @method static Builder<static>|LoyaltyReward whereType($value)
 * @method static Builder<static>|LoyaltyReward whereUpdatedAt($value)
 * @method static Builder<static>|LoyaltyReward whereUsageLimit($value)
 * @method static Builder<static>|LoyaltyReward whereValidFrom($value)
 * @method static Builder<static>|LoyaltyReward whereValidUntil($value)
 *
 * @mixin Eloquent
 */
final class LoyaltyReward extends Model
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
        'type' => LoyaltyRewardType::class,
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
            ->where(function ($q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q): void {
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

        return ! ($this->usage_limit && $this->times_used >= $this->usage_limit);
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
            LoyaltyRewardType::PERCENTAGE_DISCOUNT => $orderTotal * ($this->discount_value / 100),
            LoyaltyRewardType::FIXED_DISCOUNT => $this->discount_value,
            LoyaltyRewardType::FREE_PRODUCT => $this->freeProduct?->price ?? 0,
            LoyaltyRewardType::FREE_SHIPPING => 0, // Implement shipping logic as needed
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
            LoyaltyRewardType::PERCENTAGE_DISCOUNT => 'Percentage Discount',
            LoyaltyRewardType::FIXED_DISCOUNT => 'Fixed Amount Discount',
            LoyaltyRewardType::FREE_PRODUCT => 'Free Product',
            LoyaltyRewardType::FREE_SHIPPING => 'Free Shipping',
            default => ucwords(str_replace('_', ' ', $this->type->value))
        };
    }

    public function getValueDisplayAttribute(): string
    {
        return match ($this->type) {
            LoyaltyRewardType::PERCENTAGE_DISCOUNT => $this->discount_value.'%',
            LoyaltyRewardType::FIXED_DISCOUNT => '$'.number_format($this->discount_value, 2),
            LoyaltyRewardType::FREE_PRODUCT => $this->freeProduct?->name ?? 'Product not found',
            LoyaltyRewardType::FREE_SHIPPING => 'Free Shipping',
            default => ''
        };
    }
}
