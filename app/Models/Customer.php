<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'total_purchases',
        'total_spent',
        'last_purchase_at',
        'status',
        'loyalty_points',
        'loyalty_tier',
        'birthday',
        'marketing_consent',
    ];

    protected $casts = [
        'total_purchases' => 'integer',
        'total_spent' => 'decimal:2',
        'last_purchase_at' => 'datetime',
        'loyalty_points' => 'integer',
        'birthday' => 'date',
        'marketing_consent' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    // Loyalty tier helpers
    public function getLoyaltyTierBadgeAttribute(): array
    {
        $tiers = [
            'bronze' => ['color' => 'yellow', 'label' => 'Bronze', 'min_spent' => 0],
            'silver' => ['color' => 'gray', 'label' => 'Silver', 'min_spent' => 500],
            'gold' => ['color' => 'yellow', 'label' => 'Gold', 'min_spent' => 1500],
            'platinum' => ['color' => 'purple', 'label' => 'Platinum', 'min_spent' => 5000],
        ];

        return $tiers[$this->loyalty_tier] ?? $tiers['bronze'];
    }

    public function shouldUpgradeTier(): ?string
    {
        $currentSpent = (float) $this->total_spent;

        if ($currentSpent >= 5000 && $this->loyalty_tier !== 'platinum') {
            return 'platinum';
        } elseif ($currentSpent >= 1500 && $this->loyalty_tier === 'bronze') {
            return 'gold';
        } elseif ($currentSpent >= 1500 && $this->loyalty_tier === 'silver') {
            return 'gold';
        } elseif ($currentSpent >= 500 && $this->loyalty_tier === 'bronze') {
            return 'silver';
        }

        return null;
    }

    public function earnLoyaltyPoints(float $saleAmount, ?int $saleId = null): int
    {
        // Base: 1 point per dollar spent
        $basePoints = floor($saleAmount);

        // Tier multipliers
        $multiplier = match ($this->loyalty_tier) {
            'platinum' => 2.0,
            'gold' => 1.5,
            'silver' => 1.25,
            'bronze' => 1.0,
            default => 1.0
        };

        $pointsEarned = floor($basePoints * $multiplier);

        // Record the transaction
        $this->loyaltyTransactions()->create([
            'sale_id' => $saleId,
            'type' => 'earned',
            'points' => $pointsEarned,
            'description' => 'Points earned from purchase ($'.number_format($saleAmount, 2).')',
            'expires_at' => now()->addYear(),
        ]);

        // Update customer points
        $this->increment('loyalty_points', $pointsEarned);

        return $pointsEarned;
    }

    public function redeemLoyaltyPoints(int $pointsToRedeem, string $description = 'Points redeemed'): bool
    {
        if ($this->loyalty_points < $pointsToRedeem) {
            return false;
        }

        // Record the transaction
        $this->loyaltyTransactions()->create([
            'type' => 'redeemed',
            'points' => -$pointsToRedeem,
            'description' => $description,
        ]);

        // Update customer points
        $this->decrement('loyalty_points', $pointsToRedeem);

        return true;
    }

    public function getAvailablePoints(): int
    {
        // Get points that haven't expired
        $validPoints = $this->loyaltyTransactions()
            ->where('type', 'earned')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $redeemedPoints = $this->loyaltyTransactions()
            ->where('type', 'redeemed')
            ->sum('points'); // This will be negative

        return max(0, $validPoints + $redeemedPoints);
    }

    public function updateLoyaltyTier(): void
    {
        $newTier = $this->shouldUpgradeTier();
        if ($newTier && $newTier !== $this->loyalty_tier) {
            $this->update(['loyalty_tier' => $newTier]);

            // Award bonus points for tier upgrade
            $bonusPoints = match ($newTier) {
                'silver' => 100,
                'gold' => 250,
                'platinum' => 500,
                default => 0
            };

            if ($bonusPoints > 0) {
                $this->loyaltyTransactions()->create([
                    'type' => 'earned',
                    'points' => $bonusPoints,
                    'description' => "Bonus points for reaching {$newTier} tier",
                    'expires_at' => now()->addYear(),
                ]);

                $this->increment('loyalty_points', $bonusPoints);
            }
        }
    }

    public function isBirthdayMonth(): bool
    {
        return $this->birthday && $this->birthday->month === now()->month;
    }

    public function getPointsToNextTier(): int
    {
        $currentSpent = (float) $this->total_spent;

        return match ($this->loyalty_tier) {
            'bronze' => max(0, 500 - $currentSpent),
            'silver' => max(0, 1500 - $currentSpent),
            'gold' => max(0, 5000 - $currentSpent),
            'platinum' => 0,
            default => 500 - $currentSpent
        };
    }
}
