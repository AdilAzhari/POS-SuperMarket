<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\LoyaltyReward;
use App\Models\RewardRedemption;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

final class RewardRedemptionFactory extends Factory
{
    protected $model = RewardRedemption::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'loyalty_reward_id' => LoyaltyReward::factory(),
            'sale_id' => $this->faker->boolean(70) ? Sale::factory() : null, // 70% chance of having associated sale
            'points_used' => $this->faker->numberBetween(50, 500),
            'discount_amount' => $this->faker->randomFloat(2, 2.50, 50.00),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function withSale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sale_id' => Sale::factory(),
        ]);
    }

    public function withoutSale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'sale_id' => null,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes): array => [
            'customer_id' => $customer->id,
        ]);
    }

    public function forReward(LoyaltyReward $reward): static
    {
        return $this->state(fn (array $attributes): array => [
            'loyalty_reward_id' => $reward->id,
            'points_used' => $reward->points_required,
            'discount_amount' => $this->calculateDiscountAmount($reward),
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function percentageDiscount(): static
    {
        $percentage = $this->faker->randomElement([5, 10, 15, 20, 25]);
        $orderTotal = $this->faker->randomFloat(2, 20, 200);

        return $this->state(fn (array $attributes): array => [
            'points_used' => $percentage * 10,
            'discount_amount' => $orderTotal * ($percentage / 100),
        ]);
    }

    public function fixedDiscount(): static
    {
        $discountAmount = $this->faker->randomElement([5, 10, 15, 20, 25]);

        return $this->state(fn (array $attributes): array => [
            'points_used' => $discountAmount * 10,
            'discount_amount' => $discountAmount,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'points_used' => $this->faker->randomElement([75, 100, 125]),
            'discount_amount' => $this->faker->randomFloat(2, 5, 15), // Typical shipping cost
        ]);
    }

    private function calculateDiscountAmount(LoyaltyReward $reward): float
    {
        return match ($reward->type) {
            'percentage_discount' => $this->faker->randomFloat(2, 5, 50), // Simulated order total discount
            'fixed_discount' => $reward->discount_value,
            'free_product' => $reward->freeProduct ? $reward->freeProduct->price : $this->faker->randomFloat(2, 10, 100),
            'free_shipping' => $this->faker->randomFloat(2, 5, 15),
            default => 0
        };
    }
}
