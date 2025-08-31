<?php

declare(strict_types=1);

namespace App\Actions\Loyalty;

use App\Models\Customer;
use App\Services\LoyaltyService;

final readonly class GenerateCustomerLoyaltySummaryAction
{
    public function __construct(
        private LoyaltyService $loyaltyService
    ) {}

    /**
     * Generate comprehensive loyalty summary for customer
     */
    public function execute(Customer $customer): array
    {
        $summary = $this->loyaltyService->getCustomerLoyaltySummary($customer);

        // Add additional insights
        $summary['recommendations'] = $this->generateRecommendations($customer);
        $summary['next_tier_progress'] = $this->calculateTierProgress($customer);
        $summary['available_rewards'] = $this->getAvailableRewards($customer);

        return $summary;
    }

    private function generateRecommendations(Customer $customer): array
    {
        $recommendations = [];

        // Birthday month bonus
        if ($customer->isBirthdayMonth()) {
            $recommendations[] = [
                'type' => 'birthday_bonus',
                'message' => 'Happy Birthday! Earn double points this month.',
                'action' => 'birthday_promotion',
            ];
        }

        // Tier upgrade suggestions
        $pointsToNext = $customer->getPointsToNextTier();
        if ($pointsToNext > 0 && $pointsToNext <= 100) {
            $recommendations[] = [
                'type' => 'tier_upgrade',
                'message' => "You're only $pointsToNext away from the next tier!",
                'action' => 'spend_more',
            ];
        }

        // Reward redemption suggestions
        $availablePoints = $customer->getAvailablePoints();
        if ($availablePoints >= 100) {
            $recommendations[] = [
                'type' => 'redeem_points',
                'message' => 'You have enough points for rewards!',
                'action' => 'browse_rewards',
            ];
        }

        return $recommendations;
    }

    private function calculateTierProgress(Customer $customer): array
    {
        $currentSpent = (float) $customer->total_spent;
        $pointsToNext = $customer->getPointsToNextTier();

        $tierThresholds = [
            'bronze' => 0,
            'silver' => 500,
            'gold' => 1500,
            'platinum' => 5000,
        ];

        $currentTier = $customer->loyalty_tier->value;
        $nextTier = null;

        foreach ($tierThresholds as $tier => $threshold) {
            if ($threshold > $currentSpent) {
                $nextTier = $tier;
                break;
            }
        }

        if ($nextTier === null) {
            return [
                'current_tier' => $currentTier,
                'progress_percentage' => 100,
                'amount_to_next' => 0,
                'next_tier' => null,
            ];
        }

        $currentThreshold = $tierThresholds[array_search($currentTier, array_keys($tierThresholds))];
        $nextThreshold = $tierThresholds[$nextTier];
        $progress = ($currentSpent - $currentThreshold) / ($nextThreshold - $currentThreshold) * 100;

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'progress_percentage' => min(100, max(0, $progress)),
            'amount_to_next' => $pointsToNext,
            'current_spent' => $currentSpent,
            'next_threshold' => $nextThreshold,
        ];
    }

    private function getAvailableRewards(Customer $customer): array
    {
        $availablePoints = $customer->getAvailablePoints();

        return \App\Models\LoyaltyReward::where('is_active', true)
            ->where('points_required', '<=', $availablePoints)
            ->orderBy('points_required')
            ->get()
            ->map(fn ($reward): array => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'points_required' => $reward->points_required,
                'type' => $reward->type,
                'can_redeem' => $customer->loyalty_points >= $reward->points_required,
            ])
            ->toArray();
    }
}
