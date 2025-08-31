<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 5, 50);
        $price = $cost * fake()->randomFloat(2, 1.2, 3.0);

        return [
            'name' => fake()->words(3, true),
            'sku' => 'SKU-'.fake()->unique()->numberBetween(10000000, 99999999),
            'barcode' => 'BAR'.fake()->unique()->numberBetween(100000000, 999999999),
            'price' => round($price, 2),
            'cost' => $cost,
            'active' => true,
            'low_stock_threshold' => fake()->numberBetween(5, 20),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'image_url' => fake()->optional()->imageUrl(300, 300, 'products'),
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }

    /**
     * Indicate that the product is expensive.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => fake()->randomFloat(2, 100, 500),
            'cost' => fake()->randomFloat(2, 50, 250),
        ]);
    }

    /**
     * Indicate that the product is cheap.
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => fake()->randomFloat(2, 1, 10),
            'cost' => fake()->randomFloat(2, 0.5, 5),
        ]);
    }

    /**
     * Indicate that the product has no barcode.
     */
    public function noBarcode(): static
    {
        return $this->state(fn (array $attributes): array => [
            'barcode' => null,
        ]);
    }

    /**
     * Indicate that the product has high stock threshold.
     */
    public function highStockThreshold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'low_stock_threshold' => fake()->numberBetween(50, 100),
        ]);
    }
}
