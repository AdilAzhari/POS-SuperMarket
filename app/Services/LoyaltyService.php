<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use Exception;
use Illuminate\Support\Facades\DB;

final class LoyaltyService extends BaseService
{
    protected string $cachePrefix = 'loyalty:';

    protected int $cacheTime = 1800;

    /**
     * Process loyalty points for a sale
     */
    public function processSaleLoyalty(Sale $sale): array
    {
        if (! $sale->customer_id) {
            return ['points_earned' => 0, 'tier_upgraded' => false];
        }

        return DB::transaction(function () use ($sale): array {
            $customer = $sale->customer;

            // Update customer totals
            $customer->increment('total_purchases');
            $customer->increment('total_spent', $sale->total);
            $customer->update(['last_purchase_at' => now()]);

            // Earn points
            $pointsEarned = $customer->earnLoyaltyPoints((float) $sale->total, $sale->id);

            // Check for tier upgrade
            $originalTier = $customer->loyalty_tier;
            $customer->updateLoyaltyTier();
            $customer->refresh();

            $tierUpgraded = $originalTier !== $customer->loyalty_tier;

            $this->logInfo('Loyalty points processed for sale', [
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'points_earned' => $pointsEarned,
                'tier_upgraded' => $tierUpgraded,
                'new_tier' => $customer->loyalty_tier,
            ]);

            return [
                'points_earned' => $pointsEarned,
                'tier_upgraded' => $tierUpgraded,
                'new_tier' => $customer->loyalty_tier,
                'total_points' => $customer->loyalty_points,
            ];
        });
    }

    /**
     * Apply loyalty discount to sale
     *
     * @throws Exception
     */
    public function applyLoyaltyDiscount(Sale $sale, int $pointsToRedeem): array
    {
        if (! $sale->customer_id) {
            throw new Exception('Customer required for loyalty discount');
        }

        $customer = $sale->customer;

        // Validate points availability
        if ($customer->loyalty_points < $pointsToRedeem) {
            throw new Exception('Insufficient loyalty points');
        }

        // Calculate discount (1 point = $0.01)
        $discountAmount = $pointsToRedeem * 0.01;

        // Ensure discount doesn't exceed sale total
        $maxDiscount = $sale->subtotal;
        $discountAmount = min($discountAmount, $maxDiscount);
        $actualPointsUsed = floor($discountAmount / 0.01);

        return DB::transaction(function () use ($sale, $customer, $actualPointsUsed, $discountAmount): array {
            // Apply discount to sale
            $sale->increment('discount', $discountAmount);
            $sale->decrement('total', $discountAmount);

            // Redeem points
            $customer->redeemLoyaltyPoints(
                $actualPointsUsed,
                "Points redeemed for sale #$sale->id"
            );

            $this->logInfo('Loyalty discount applied', [
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'points_redeemed' => $actualPointsUsed,
                'discount_amount' => $discountAmount,
            ]);

            return [
                'points_redeemed' => $actualPointsUsed,
                'discount_amount' => $discountAmount,
                'remaining_points' => $customer->fresh()->loyalty_points,
            ];
        });
    }

    /**
     * Get customer loyalty summary
     */
    public function getCustomerLoyaltySummary(Customer $customer): array
    {
        return $this->remember("customer:$customer->id:summary", function () use ($customer): array {
            $recentTransactions = $customer->loyaltyTransactions()
                ->latest()
                ->take(10)
                ->get();

            $pointsExpiringThisMonth = $customer->loyaltyTransactions()
                ->where('type', 'earned')
                ->where('expires_at', '<=', now()->endOfMonth())
                ->where('expires_at', '>', now())
                ->sum('points');

            return [
                'customer' => $customer,
                'tier_badge' => $customer->loyalty_tier_badge,
                'points_to_next_tier' => $customer->getPointsToNextTier(),
                'recent_transactions' => $recentTransactions,
                'points_expiring_soon' => $pointsExpiringThisMonth,
                'lifetime_points_earned' => $customer->loyaltyTransactions()
                    ->where('type', 'earned')
                    ->sum('points'),
                'is_birthday_month' => $customer->isBirthdayMonth(),
            ];
        }, $this->cacheTime);
    }

    /**
     * Get loyalty program analytics
     */
    public function getLoyaltyAnalytics(): array
    {
        return $this->remember('analytics', function (): array {
            $customers = Customer::query()->whereNotNull('loyalty_points')->get();
            $totalCustomers = Customer::query()->count();
            $loyaltyCustomers = $customers->count();

            return [
                'total_customers' => $totalCustomers,
                'loyalty_customers' => $loyaltyCustomers,
                'enrollment_rate' => $totalCustomers > 0 ? round(($loyaltyCustomers / $totalCustomers) * 100, 1) : 0,
                'customers_by_tier' => $customers->groupBy('loyalty_tier')->map->count(),
                'total_points_issued' => LoyaltyTransaction::query()->where('type', 'earned')->sum('points'),
                'total_points_redeemed' => abs(LoyaltyTransaction::query()->where('type', 'redeemed')->sum('points')),
                'top_customers' => $customers->sortByDesc('loyalty_points')->take(10)->values(),
                'recent_transactions' => LoyaltyTransaction::with(['customer', 'sale'])
                    ->latest()
                    ->take(20)
                    ->get(),
                'birthday_customers_this_month' => $customers->filter->isBirthdayMonth()->count(),
            ];
        }, 3600); // Cache for 1 hour
    }

    /**
     * Send birthday rewards
     */
    public function sendBirthdayRewards(): int
    {
        $birthdayCustomers = Customer::query()->whereNotNull('birthday')
            ->whereMonth('birthday', now()->month)
            ->whereDay('birthday', now()->day)
            ->where('marketing_consent', true)
            ->get();

        $rewardsGiven = 0;

        foreach ($birthdayCustomers as $customer) {
            // Give birthday bonus points
            $bonusPoints = 100;

            $customer->loyaltyTransactions()->create([
                'type' => 'earned',
                'points' => $bonusPoints,
                'description' => 'Happy Birthday! Special bonus points',
                'expires_at' => now()->addMonths(3), // Birthday points expire in 3 months
            ]);

            $customer->increment('loyalty_points', $bonusPoints);
            $rewardsGiven++;

            $this->logInfo('Birthday reward given', [
                'customer_id' => $customer->id,
                'bonus_points' => $bonusPoints,
            ]);
        }

        return $rewardsGiven;
    }

    /**
     * Expire old points
     */
    public function expireOldPoints(): int
    {
        $expiredTransactions = LoyaltyTransaction::query()->where('type', 'earned')
            ->where('expires_at', '<', now())
            ->whereNotNull('expires_at')
            ->get();

        $totalExpired = 0;

        foreach ($expiredTransactions as $transaction) {
            // Create expiry transaction
            $transaction->customer->loyaltyTransactions()->create([
                'type' => 'expired',
                'points' => -$transaction->points,
                'description' => "Points expired from transaction #$transaction->id",
                'metadata' => ['original_transaction_id' => $transaction->id],
            ]);

            $transaction->customer->decrement('loyalty_points', $transaction->points);
            $totalExpired += $transaction->points;

            // Mark original transaction as processed
            $transaction->update(['expires_at' => null]);
        }

        $this->logInfo('Expired loyalty points', ['total_expired' => $totalExpired]);

        return $totalExpired;
    }

    /**
     * Calculate points value in currency
     */
    public function pointsToMoney(int $points): float
    {
        return $points * 0.01; // 1 point = $0.01
    }

    /**
     * Calculate currency to points
     */
    public function moneyToPoints(float $amount): int
    {
        return floor($amount * 100); // $1 = 100 points
    }
}
