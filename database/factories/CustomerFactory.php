<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
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
        ];
    }

    /**
     * Customer with purchase history
     */
    public function withPurchases(): static
    {
        return $this->state(fn (array $attributes) => [
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
        return $this->state(fn (array $attributes) => [
            'total_purchases' => fake()->numberBetween(50, 200),
            'total_spent' => fake()->randomFloat(2, 10000, 50000),
            'last_purchase_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'status' => 'vip',
        ]);
    }

    /**
     * Inactive customer
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'last_purchase_at' => fake()->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Customer without email
     */
    public function noEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }
}
