<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->bothify('STK-######')),
            'product_id' => Product::factory(),
            'store_id' => Store::factory(),
            'type' => fake()->randomElement(['in', 'out', 'transfer', 'adjustment']),
            'quantity' => fake()->numberBetween(1, 100),
            'reason' => fake()->randomElement(['purchase', 'sale', 'return', 'damaged', 'expired', 'recount']),
            'notes' => fake()->optional()->sentence(),
            'from_store_id' => null,
            'to_store_id' => null,
            'user_id' => User::factory(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Stock coming in (purchase, return)
     */
    public function stockIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'reason' => fake()->randomElement(['purchase', 'return']),
            'quantity' => fake()->numberBetween(10, 200),
        ]);
    }

    /**
     * Stock going out (sale, damaged)
     */
    public function stockOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'reason' => fake()->randomElement(['sale', 'damaged', 'expired']),
            'quantity' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Transfer between stores
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'transfer',
            'reason' => 'transfer',
            'from_store_id' => Store::factory(),
            'to_store_id' => Store::factory(),
            'quantity' => fake()->numberBetween(5, 100),
        ]);
    }

    /**
     * Stock adjustment
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjustment',
            'reason' => 'recount',
            'quantity' => fake()->numberBetween(-50, 50),
            'notes' => 'Stock count adjustment after inventory',
        ]);
    }

    /**
     * Large quantity movement
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(500, 2000),
            'reason' => 'purchase',
            'type' => 'in',
        ]);
    }
}
