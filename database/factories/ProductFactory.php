<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'barcode' => strtoupper(fake()->bothify('BAR#########')),
            'price' => fake()->randomFloat(2, 1, 100),
            'cost' => fake()->randomFloat(2, 0, 50),
            'active' => true,
            'low_stock_threshold' => fake()->numberBetween(0, 10),
        ];
    }
}
