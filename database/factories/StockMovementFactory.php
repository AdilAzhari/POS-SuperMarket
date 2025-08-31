<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->bothify('STK-######')),
            'product_id' => Product::factory(),
            'store_id' => Store::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'reason' => fake()->randomElement(StockMovementReason::cases()),
            'notes' => fake()->optional()->sentence(),
            'from_store_id' => null,
            'to_store_id' => null,
            'user_id' => User::factory(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'type' => fake()->randomElement(StockMovementType::cases()),
        ];
    }

    /**
     * Stock coming in (purchase, return)
     */
    public function stockIn(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StockMovementType::ADDITION,
            'reason' => fake()->randomElement([StockMovementReason::PURCHASE, StockMovementReason::RETURN]),
            'quantity' => fake()->numberBetween(10, 200),
        ]);
    }

    /**
     * Stock going out (sale, damaged)
     */
    public function stockOut(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => StockMovementType::REDUCTION,
            'reason' => fake()->randomElement([StockMovementReason::SALE, StockMovementReason::DAMAGED, StockMovementReason::EXPIRED]),
            'quantity' => fake()->numberBetween(1, 50),
        ]);
    }

    /**
     * Transfer between stores
     */
    public function transfer(): static
    {
        return $this->state(function (array $attributes): array {
            // Ensure store_id is treated as the source store for transfer_out
            $fromStoreId = $attributes['store_id'] ?? Store::factory();

            return [
                'type' => StockMovementType::TRANSFER_OUT,
                'reason' => StockMovementReason::TRANSFER,
                'from_store_id' => $fromStoreId,
                'to_store_id' => Store::factory(),
                'quantity' => fake()->numberBetween(5, 100),
            ];
        });
    }

    /**
     * Stock adjustment
     */
    public function adjustment(): static
    {
        return $this->state(fn (array $attributes): array => [
            // Use a valid enum and non-negative quantity for unsigned column
            'type' => fake()->randomElement([StockMovementType::ADDITION, StockMovementType::REDUCTION]),
            'reason' => StockMovementReason::RECOUNT,
            'quantity' => fake()->numberBetween(1, 50),
            'notes' => 'Stock count adjustment after inventory',
        ]);
    }

    /**
     * Large quantity movement
     */
    public function bulk(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => fake()->numberBetween(500, 2000),
            'reason' => StockMovementReason::PURCHASE,
            'type' => StockMovementType::ADDITION,
        ]);
    }
}
