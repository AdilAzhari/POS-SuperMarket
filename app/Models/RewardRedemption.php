<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RewardRedemptionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $loyalty_reward_id
 * @property int|null $sale_id
 * @property int $points_used
 * @property numeric $discount_amount
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Customer $customer
 * @property-read string $formatted_discount
 * @property-read string $formatted_points
 * @property-read LoyaltyReward $loyaltyReward
 * @property-read Sale|null $sale
 *
 * @method static RewardRedemptionFactory factory($count = null, $state = [])
 * @method static Builder<static>|RewardRedemption forCustomer($customerId)
 * @method static Builder<static>|RewardRedemption forReward($rewardId)
 * @method static Builder<static>|RewardRedemption newModelQuery()
 * @method static Builder<static>|RewardRedemption newQuery()
 * @method static Builder<static>|RewardRedemption query()
 * @method static Builder<static>|RewardRedemption recent($days = 30)
 * @method static Builder<static>|RewardRedemption whereCreatedAt($value)
 * @method static Builder<static>|RewardRedemption whereCustomerId($value)
 * @method static Builder<static>|RewardRedemption whereDiscountAmount($value)
 * @method static Builder<static>|RewardRedemption whereId($value)
 * @method static Builder<static>|RewardRedemption whereLoyaltyRewardId($value)
 * @method static Builder<static>|RewardRedemption wherePointsUsed($value)
 * @method static Builder<static>|RewardRedemption whereSaleId($value)
 * @method static Builder<static>|RewardRedemption whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class RewardRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loyalty_reward_id',
        'sale_id',
        'points_used',
        'discount_amount',
    ];

    protected $casts = [
        'points_used' => 'integer',
        'discount_amount' => 'decimal:2',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyReward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    // Scopes
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForReward($query, $rewardId)
    {
        return $query->where('loyalty_reward_id', $rewardId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getFormattedDiscountAttribute(): string
    {
        return '$'.number_format($this->discount_amount, 2);
    }

    public function getFormattedPointsAttribute(): string
    {
        return number_format($this->points_used).' points';
    }
}
