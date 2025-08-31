<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoyaltyReward;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

final class LoyaltyRewardFactory extends Factory
{
    protected $model = LoyaltyReward::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['percentage_discount', 'fixed_discount', 'free_product', 'free_shipping']);

        return [
            'name' => $this->getNameForType($type),
            'description' => $this->getDescriptionForType($type),
            'points_required' => $this->getPointsRequiredForType($type),
            'type' => $type,
            'discount_value' => $this->getDiscountValueForType($type),
            'free_product_id' => $type === 'free_product' ? Product::factory() : null,
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'valid_from' => $this->faker->optional(0.3)->dateTimeBetween('-1 month', 'now'),
            'valid_until' => $this->faker->optional(0.4)->dateTimeBetween('now', '+6 months'),
            'usage_limit' => $this->faker->optional(0.3)->numberBetween(10, 1000),
            'times_used' => 0,
        ];
    }

    public function percentageDiscount(): static
    {
        $percentage = $this->faker->randomElement([5, 10, 15, 20, 25]);

        return $this->state(fn (array $attributes): array => [
            'name' => "{$percentage}% Off Next Purchase",
            'description' => "Get {$percentage}% discount on your next purchase",
            'type' => 'percentage_discount',
            'discount_value' => $percentage,
            'points_required' => $percentage * 10, // 10 points per 1%
            'free_product_id' => null,
        ]);
    }

    public function fixedDiscount(): static
    {
        $amount = $this->faker->randomElement([5, 10, 15, 20, 25]);

        return $this->state(fn (array $attributes): array => [
            'name' => "\${$amount} Off Purchase",
            'description' => "Get \${$amount} off your next purchase",
            'type' => 'fixed_discount',
            'discount_value' => $amount,
            'points_required' => $amount * 10, // 10 points per dollar
            'free_product_id' => null,
        ]);
    }

    public function freeProduct(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Free Product Reward',
            'description' => 'Choose any eligible product for free',
            'type' => 'free_product',
            'discount_value' => 0,
            'points_required' => $this->faker->randomElement([300, 500, 750, 1000]),
            'free_product_id' => Product::factory(),
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Free Shipping',
            'description' => 'No shipping charges on your order',
            'type' => 'free_shipping',
            'discount_value' => 0,
            'points_required' => $this->faker->randomElement([75, 100, 125]),
            'free_product_id' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withUsageLimit(int $limit): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit' => $limit,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'valid_until' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
            'is_active' => false,
        ]);
    }

    private function getNameForType(string $type): string
    {
        return match ($type) {
            'percentage_discount' => $this->faker->randomElement([
                '10% Off Next Purchase',
                '15% Discount Reward',
                '20% Off Everything',
                '25% VIP Discount',
            ]),
            'fixed_discount' => $this->faker->randomElement([
                '$5 Off Purchase',
                '$10 Discount Voucher',
                '$15 Off Coupon',
                '$20 Store Credit',
            ]),
            'free_product' => $this->faker->randomElement([
                'Free Product Reward',
                'Complimentary Item',
                'Free Gift',
                'Bonus Product',
            ]),
            'free_shipping' => $this->faker->randomElement([
                'Free Shipping',
                'Complimentary Delivery',
                'No Delivery Charge',
                'Free Express Shipping',
            ])
        };
    }

    private function getDescriptionForType(string $type): string
    {
        return match ($type) {
            'percentage_discount' => $this->faker->randomElement([
                'Get a percentage discount on your next purchase',
                'Enjoy savings with this discount reward',
                'Save money on your favorite items',
                'Exclusive discount for loyal customers',
            ]),
            'fixed_discount' => $this->faker->randomElement([
                'Fixed dollar amount off your purchase',
                'Instant savings on any order',
                'Money off your next shopping trip',
                'Direct discount applied at checkout',
            ]),
            'free_product' => $this->faker->randomElement([
                'Choose any eligible product for free',
                'Complimentary item of your choice',
                'Free product from selected categories',
                'No-cost item as a thank you gift',
            ]),
            'free_shipping' => $this->faker->randomElement([
                'No shipping charges on your order',
                'Complimentary delivery service',
                'Free shipping on any purchase',
                'We cover the delivery cost',
            ])
        };
    }

    private function getPointsRequiredForType(string $type): int
    {
        return match ($type) {
            'percentage_discount' => $this->faker->randomElement([100, 150, 200, 250, 300]),
            'fixed_discount' => $this->faker->randomElement([50, 100, 150, 200]),
            'free_product' => $this->faker->randomElement([200, 300, 500, 750, 1000]),
            'free_shipping' => $this->faker->randomElement([75, 100, 125]),
        };
    }

    private function getDiscountValueForType(string $type): ?float
    {
        return match ($type) {
            'percentage_discount' => $this->faker->randomElement([5, 10, 15, 20, 25]),
            'fixed_discount' => $this->faker->randomElement([5.00, 10.00, 15.00, 20.00, 25.00]),
            'free_product', 'free_shipping' => 0,
        };
    }
}
