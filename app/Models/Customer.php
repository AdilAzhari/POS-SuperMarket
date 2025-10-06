<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\LoyaltyTier;
use App\Enums\LoyaltyTransactionType;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $address
 * @property int $total_purchases
 * @property numeric $total_spent
 * @property CarbonImmutable|null $last_purchase_at
 * @property CustomerStatus $status
 * @property int $loyalty_points
 * @property LoyaltyTier $loyalty_tier
 * @property CarbonImmutable|null $birthday
 * @property bool $marketing_consent
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read array $loyalty_tier_badge
 * @property-read Collection<int, LoyaltyTransaction> $loyaltyTransactions
 * @property-read int|null $loyalty_transactions_count
 * @property-read Collection<int, RewardRedemption> $rewardRedemptions
 * @property-read int|null $reward_redemptions_count
 * @property-read Collection<int, Sale> $sales
 * @property-read int|null $sales_count
 *
 * @method static CustomerFactory factory($count = null, $state = [])
 * @method static Builder<static>|Customer newModelQuery()
 * @method static Builder<static>|Customer newQuery()
 * @method static Builder<static>|Customer query()
 * @method static Builder<static>|Customer whereAddress($value)
 * @method static Builder<static>|Customer whereBirthday($value)
 * @method static Builder<static>|Customer whereCreatedAt($value)
 * @method static Builder<static>|Customer whereEmail($value)
 * @method static Builder<static>|Customer whereId($value)
 * @method static Builder<static>|Customer whereLastPurchaseAt($value)
 * @method static Builder<static>|Customer whereLoyaltyPoints($value)
 * @method static Builder<static>|Customer whereLoyaltyTier($value)
 * @method static Builder<static>|Customer whereMarketingConsent($value)
 * @method static Builder<static>|Customer whereName($value)
 * @method static Builder<static>|Customer wherePhone($value)
 * @method static Builder<static>|Customer whereStatus($value)
 * @method static Builder<static>|Customer whereTotalPurchases($value)
 * @method static Builder<static>|Customer whereTotalSpent($value)
 * @method static Builder<static>|Customer whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class Customer extends Model
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
        'status' => CustomerStatus::class,
        'loyalty_tier' => LoyaltyTier::class,
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
        if (! $this->loyalty_tier) {
            return ['color' => 'yellow', 'label' => 'Bronze', 'min_spent' => 0];
        }

        $tiers = [
            LoyaltyTier::BRONZE->value => ['color' => 'yellow', 'label' => 'Bronze', 'min_spent' => 0],
            LoyaltyTier::SILVER->value => ['color' => 'gray', 'label' => 'Silver', 'min_spent' => 500],
            LoyaltyTier::GOLD->value => ['color' => 'yellow', 'label' => 'Gold', 'min_spent' => 1500],
            LoyaltyTier::PLATINUM->value => ['color' => 'purple', 'label' => 'Platinum', 'min_spent' => 5000],
        ];

        return $tiers[$this->loyalty_tier->value] ?? $tiers[LoyaltyTier::BRONZE->value];
    }

    public function shouldUpgradeTier(): ?LoyaltyTier
    {
        $currentSpent = (float) $this->total_spent;
        if ($currentSpent >= 5000 && $this->loyalty_tier !== LoyaltyTier::PLATINUM) {
            return LoyaltyTier::PLATINUM;
        }
        if ($currentSpent >= 1500 && $this->loyalty_tier === LoyaltyTier::BRONZE) {
            return LoyaltyTier::GOLD;
        }
        if ($currentSpent >= 1500 && $this->loyalty_tier === LoyaltyTier::SILVER) {
            return LoyaltyTier::GOLD;
        }

        if ($currentSpent >= 500 && $this->loyalty_tier === LoyaltyTier::BRONZE) {
            return LoyaltyTier::SILVER;
        }

        return null;
    }

    public function earnLoyaltyPoints(float $saleAmount, ?int $saleId = null): int
    {
        // Base: 1 point per dollar spent
        $basePoints = floor($saleAmount);

        // Tier multipliers
        $multiplier = match ($this->loyalty_tier) {
            LoyaltyTier::PLATINUM => 2.0,
            LoyaltyTier::GOLD => 1.5,
            LoyaltyTier::SILVER => 1.25,
            LoyaltyTier::BRONZE => 1.0,
            default => 1.0
        };

        $pointsEarned = (int) floor($basePoints * $multiplier);

        // Record the transaction
        $this->loyaltyTransactions()->create([
            'sale_id' => $saleId,
            'type' => LoyaltyTransactionType::EARNED,
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
            'type' => LoyaltyTransactionType::REDEEMED,
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
            ->where('type', LoyaltyTransactionType::EARNED)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $redeemedPoints = $this->loyaltyTransactions()
            ->where('type', LoyaltyTransactionType::REDEEMED)
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
                LoyaltyTier::SILVER => 100,
                LoyaltyTier::GOLD => 250,
                LoyaltyTier::PLATINUM => 500,
                default => 0
            };

            if ($bonusPoints > 0) {
                $this->loyaltyTransactions()->create([
                    'type' => LoyaltyTransactionType::EARNED,
                    'points' => $bonusPoints,
                    'description' => "Bonus points for reaching $newTier->value tier",
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
            LoyaltyTier::BRONZE => max(0, 500 - $currentSpent),
            LoyaltyTier::SILVER => max(0, 1500 - $currentSpent),
            LoyaltyTier::GOLD => max(0, 5000 - $currentSpent),
            LoyaltyTier::PLATINUM => 0,
            default => 500 - $currentSpent
        };
    }
}
