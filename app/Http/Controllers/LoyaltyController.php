<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\RewardRedemption;
use App\Services\LoyaltyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    public function __construct(
        private readonly LoyaltyService $loyaltyService
    ) {}

    /**
     * Get customer loyalty summary
     */
    public function getCustomerSummary(Customer $customer): JsonResponse
    {
        try {
            $summary = $this->loyaltyService->getCustomerLoyaltySummary($customer);

            return response()->json([
                'data' => $summary,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch customer loyalty summary',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply loyalty discount to a sale
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'sale_id' => 'required|integer|exists:sales,id',
            'points_to_redeem' => 'required|integer|min:1',
        ]);

        try {
            $sale = \App\Models\Sale::findOrFail($request->sale_id);

            $result = $this->loyaltyService->applyLoyaltyDiscount(
                $sale,
                $request->points_to_redeem
            );

            return response()->json([
                'message' => 'Loyalty discount applied successfully',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to apply loyalty discount',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get loyalty program analytics
     */
    public function analytics(): JsonResponse
    {
        try {
            $analytics = $this->loyaltyService->getLoyaltyAnalytics();

            return response()->json([
                'data' => $analytics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch loyalty analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loyalty program configuration
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'tiers' => [
                ['name' => 'Bronze', 'min_spent' => 0, 'multiplier' => 1.0, 'color' => 'yellow'],
                ['name' => 'Silver', 'min_spent' => 500, 'multiplier' => 1.25, 'color' => 'gray'],
                ['name' => 'Gold', 'min_spent' => 1500, 'multiplier' => 1.5, 'color' => 'yellow'],
                ['name' => 'Platinum', 'min_spent' => 5000, 'multiplier' => 2.0, 'color' => 'purple'],
            ],
            'point_value' => 0.01, // $0.01 per point
            'earn_rate' => 1, // 1 point per dollar
            'expiry_months' => 12,
        ]);
    }

    /**
     * Manually adjust customer points (admin only)
     */
    public function adjustPoints(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'points' => 'required|integer',
            'reason' => 'required|string|max:255',
            'type' => 'required|in:earned,redeemed,adjustment',
        ]);

        try {
            $points = $request->points;
            $reason = $request->reason;
            $type = $request->type;

            if ($type === 'redeemed' && $customer->loyalty_points < abs($points)) {
                return response()->json([
                    'error' => 'Insufficient points for redemption',
                ], 422);
            }

            // Create loyalty transaction
            $customer->loyaltyTransactions()->create([
                'type' => $type,
                'points' => $type === 'redeemed' ? -abs($points) : abs($points),
                'description' => $reason,
                'expires_at' => $type === 'earned' ? now()->addYear() : null,
            ]);

            // Update customer points
            if ($type === 'redeemed') {
                $customer->decrement('loyalty_points', abs($points));
            } else {
                $customer->increment('loyalty_points', abs($points));
            }

            return response()->json([
                'message' => 'Points adjusted successfully',
                'data' => [
                    'points_adjusted' => $points,
                    'new_total' => $customer->fresh()->loyalty_points,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to adjust points',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send birthday rewards (admin only)
     */
    public function sendBirthdayRewards(): JsonResponse
    {
        try {
            $rewardsGiven = $this->loyaltyService->sendBirthdayRewards();

            return response()->json([
                'message' => "Birthday rewards sent to {$rewardsGiven} customers",
                'rewards_given' => $rewardsGiven,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to send birthday rewards',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expire old points (admin only)
     */
    public function expireOldPoints(): JsonResponse
    {
        try {
            $expiredPoints = $this->loyaltyService->expireOldPoints();

            return response()->json([
                'message' => "Expired {$expiredPoints} points",
                'expired_points' => $expiredPoints,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to expire points',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate points for an amount
     */
    public function calculatePoints(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'customer_id' => 'nullable|integer|exists:customers,id',
        ]);

        $amount = $request->amount;
        $customerId = $request->customer_id;

        // Base points
        $basePoints = floor($amount);

        // Apply tier multiplier if customer provided
        if ($customerId) {
            $customer = Customer::find($customerId);
            $multiplier = match ($customer->loyalty_tier ?? 'bronze') {
                'platinum' => 2.0,
                'gold' => 1.5,
                'silver' => 1.25,
                'bronze' => 1.0,
                default => 1.0
            };
            $points = floor($basePoints * $multiplier);
        } else {
            $points = $basePoints;
        }

        return response()->json([
            'data' => [
                'amount' => $amount,
                'base_points' => $basePoints,
                'total_points' => $points,
                'multiplier' => $multiplier ?? 1.0,
                'tier' => $customer->loyalty_tier ?? 'bronze',
            ],
        ]);
    }

    /**
     * Get all loyalty rewards
     */
    public function getRewards(): JsonResponse
    {
        try {
            $rewards = LoyaltyReward::where('is_active', true)
                ->orderBy('points_required')
                ->get();

            return response()->json([
                'data' => $rewards,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch rewards',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new loyalty reward
     */
    public function createReward(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage_discount,fixed_discount,free_product,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'points_required' => 'required|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'product_id' => 'nullable|integer|exists:products,id',
            'is_active' => 'boolean',
        ]);

        try {
            $reward = LoyaltyReward::create($request->all());

            return response()->json([
                'message' => 'Reward created successfully',
                'data' => $reward,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to create reward',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a loyalty reward
     */
    public function updateReward(Request $request, LoyaltyReward $reward): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage_discount,fixed_discount,free_product,free_shipping',
            'discount_value' => 'sometimes|numeric|min:0',
            'points_required' => 'sometimes|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'product_id' => 'nullable|integer|exists:products,id',
            'is_active' => 'boolean',
        ]);

        try {
            $reward->update($request->all());

            return response()->json([
                'message' => 'Reward updated successfully',
                'data' => $reward->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to update reward',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a loyalty reward
     */
    public function deleteReward(LoyaltyReward $reward): JsonResponse
    {
        try {
            $reward->delete();

            return response()->json([
                'message' => 'Reward deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to delete reward',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Redeem a reward
     */
    public function redeemReward(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'reward_id' => 'required|integer|exists:loyalty_rewards,id',
            'sale_id' => 'nullable|integer|exists:sales,id',
        ]);

        try {
            $customer = Customer::findOrFail($request->customer_id);
            $reward = LoyaltyReward::findOrFail($request->reward_id);

            // Check if customer has enough points
            if ($customer->loyalty_points < $reward->points_required) {
                return response()->json([
                    'error' => 'Insufficient loyalty points',
                    'required' => $reward->points_required,
                    'available' => $customer->loyalty_points,
                ], 422);
            }

            // Check if reward is available
            if (! $reward->isAvailable()) {
                return response()->json([
                    'error' => 'Reward is not available',
                ], 422);
            }

            // Calculate discount amount
            $saleTotal = $request->sale_id ? \App\Models\Sale::find($request->sale_id)->total : 0;
            $discountAmount = $reward->getDiscountAmount($saleTotal);

            // Create redemption record
            $redemption = RewardRedemption::create([
                'customer_id' => $customer->id,
                'loyalty_reward_id' => $reward->id,
                'sale_id' => $request->sale_id,
                'points_used' => $reward->points_required,
                'discount_amount' => $discountAmount,
            ]);

            // Deduct points from customer
            $customer->decrement('loyalty_points', $reward->points_required);

            // Create loyalty transaction
            $customer->loyaltyTransactions()->create([
                'type' => 'redeemed',
                'points' => -$reward->points_required,
                'description' => "Redeemed: {$reward->name}",
                'reward_redemption_id' => $redemption->id,
            ]);

            return response()->json([
                'message' => 'Reward redeemed successfully',
                'data' => [
                    'redemption' => $redemption->load(['loyaltyReward']),
                    'discount_amount' => $discountAmount,
                    'remaining_points' => $customer->fresh()->loyalty_points,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to redeem reward',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get reward redemptions
     */
    public function getRedemptions(Request $request): JsonResponse
    {
        $query = RewardRedemption::with(['customer', 'loyaltyReward', 'sale'])
            ->orderBy('created_at', 'desc');

        if ($request->customer_id) {
            $query->forCustomer($request->customer_id);
        }

        if ($request->reward_id) {
            $query->forReward($request->reward_id);
        }

        if ($request->days) {
            $query->recent($request->days);
        }

        $redemptions = $query->paginate($request->per_page ?? 20);

        return response()->json($redemptions);
    }

    /**
     * Get customer loyalty transactions
     */
    public function getTransactions(Request $request, Customer $customer): JsonResponse
    {
        $query = $customer->loyaltyTransactions()
            ->orderBy('created_at', 'desc');

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->days) {
            $query->where('created_at', '>=', now()->subDays($request->days));
        }

        $transactions = $query->paginate($request->per_page ?? 20);

        return response()->json($transactions);
    }
}
