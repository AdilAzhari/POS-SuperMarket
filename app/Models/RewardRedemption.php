<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
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
