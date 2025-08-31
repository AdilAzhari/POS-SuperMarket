<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
final class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'total_purchases' => 0,
            'total_spent' => 0,
            'last_purchase_at' => null,
            'status' => 'active',
            'loyalty_points' => 0,
            'loyalty_tier' => 'bronze',
            'birthday' => fake()->optional(0.7)->date(), // 70% chance of having birthday
            'marketing_consent' => fake()->boolean(60), // 60% consent rate
        ];
    }

    /**
     * Customer with purchase history
     */
    public function withPurchases(): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_purchases' => fake()->numberBetween(5, 50),
            'total_spent' => fake()->randomFloat(2, 100, 5000),
            'last_purchase_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * VIP customer with high spending
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes): array => [
            'total_purchases' => fake()->numberBetween(50, 200),
            'total_spent' => fake()->randomFloat(2, 10000, 50000),
            'last_purchase_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'status' => 'active',
            'loyalty_tier' => 'platinum',
            'loyalty_points' => fake()->numberBetween(2000, 5000),
        ]);
    }

    /**
     * Inactive customer
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
            'last_purchase_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Customer without email
     */
    public function noEmail(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email' => null,
        ]);
    }

    /**
     * Customer with loyalty points
     */
    public function withLoyaltyPoints(): static
    {
        return $this->state(fn (array $attributes): array => [
            'loyalty_points' => fake()->numberBetween(50, 500),
            'loyalty_tier' => fake()->randomElement(['bronze', 'silver']),
        ]);
    }

    /**
     * Silver tier customer
     */
    public function silver(): static
    {
        return $this->state(fn (array $attributes): array => [
            'loyalty_points' => fake()->numberBetween(100, 1000),
            'loyalty_tier' => 'silver',
            'total_spent' => fake()->randomFloat(2, 500, 1500),
        ]);
    }

    /**
     * Gold tier customer
     */
    public function gold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'loyalty_points' => fake()->numberBetween(500, 2000),
            'loyalty_tier' => 'gold',
            'total_spent' => fake()->randomFloat(2, 1500, 5000),
        ]);
    }

    /**
     * Platinum tier customer
     */
    public function platinum(): static
    {
        return $this->state(fn (array $attributes): array => [
            'loyalty_points' => fake()->numberBetween(1000, 5000),
            'loyalty_tier' => 'platinum',
            'total_spent' => fake()->randomFloat(2, 5000, 20000),
        ]);
    }

    /**
     * Customer with birthday
     */
    public function withBirthday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'birthday' => fake()->date(),
        ]);
    }

    /**
     * Customer with marketing consent
     */
    public function marketingConsent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'marketing_consent' => true,
        ]);
    }
}
