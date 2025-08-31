<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\RewardRedemption;
use App\Models\Sale;
use Illuminate\Database\Seeder;

final class RewardRedemptionSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::where('loyalty_points', '>', 50)->get();
        $rewards = LoyaltyReward::where('is_active', true)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers with sufficient points found.');

            return;
        }

        if ($rewards->isEmpty()) {
            $this->command->warn('No active rewards found. Please run LoyaltyRewardSeeder first.');

            return;
        }

        $redemptionsCreated = 0;

        // Create redemptions for customers with enough points
        foreach ($customers as $customer) {
            // Each customer has a 60% chance of having redemptions
            if (random_int(1, 100) > 60) {
                continue;
            }

            // Create 1-3 redemptions per eligible customer
            $redemptionCount = random_int(1, 3);
            $customerPoints = $customer->loyalty_points;

            for ($i = 0; $i < $redemptionCount; $i++) {
                // Find rewards the customer can afford
                $affordableRewards = $rewards->filter(fn ($reward): bool => $reward->points_required <= $customerPoints);

                if ($affordableRewards->isEmpty()) {
                    break; // Customer can't afford any more rewards
                }

                $reward = $affordableRewards->random();

                // Calculate discount amount based on reward type
                $discountAmount = $this->calculateDiscountAmount($reward);

                // 70% chance of having an associated sale
                $sale = random_int(1, 100) <= 70
                    ? Sale::where('customer_id', $customer->id)->inRandomOrder()->first()
                    : null;

                $redemption = RewardRedemption::create([
                    'customer_id' => $customer->id,
                    'loyalty_reward_id' => $reward->id,
                    'sale_id' => $sale?->id,
                    'points_used' => $reward->points_required,
                    'discount_amount' => $discountAmount,
                    'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);

                // Update customer points and reward usage
                $customerPoints -= $reward->points_required;
                $reward->increment('times_used');

                $redemptionsCreated++;

                // Stop if customer runs out of points
                if ($customerPoints < 50) {
                    break;
                }
            }

            // Update the customer's actual loyalty points
            $customer->update(['loyalty_points' => $customerPoints]);
        }

        // Create some recent redemptions for testing
        $recentCustomers = Customer::where('loyalty_points', '>', 100)->limit(5)->get();
        foreach ($recentCustomers as $customer) {
            $affordableReward = $rewards->filter(fn ($reward): bool => $reward->points_required <= $customer->loyalty_points)->first();

            if ($affordableReward) {
                RewardRedemption::create([
                    'customer_id' => $customer->id,
                    'loyalty_reward_id' => $affordableReward->id,
                    'sale_id' => Sale::where('customer_id', $customer->id)->latest()->first()?->id,
                    'points_used' => $affordableReward->points_required,
                    'discount_amount' => $this->calculateDiscountAmount($affordableReward),
                    'created_at' => fake()->dateTimeBetween('-7 days', 'now'),
                ]);

                $customer->decrement('loyalty_points', $affordableReward->points_required);
                $affordableReward->increment('times_used');

                $redemptionsCreated++;
            }
        }

        $this->command->info("Created {$redemptionsCreated} reward redemptions");
    }

    private function calculateDiscountAmount(LoyaltyReward $reward): float
    {
        return match ($reward->type) {
            'percentage_discount' => $this->calculatePercentageDiscount($reward->discount_value),
            'fixed_discount' => (float) $reward->discount_value,
            'free_product' => $reward->freeProduct
                ? (float) $reward->freeProduct->price
                : fake()->randomFloat(2, 10, 50),
            'free_shipping' => fake()->randomFloat(2, 5.99, 15.99), // Typical shipping costs
            default => 0.00
        };
    }

    private function calculatePercentageDiscount(float $percentage): float
    {
        // Simulate an order total and calculate discount
        $simulatedOrderTotal = fake()->randomFloat(2, 25, 200);

        return round($simulatedOrderTotal * ($percentage / 100), 2);
    }
}
