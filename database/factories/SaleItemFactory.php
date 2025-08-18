<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1, 100);
        $quantity = fake()->numberBetween(1, 5);
        $discount = fake()->randomFloat(2, 0, $price * 0.2);
        $tax = ($price - $discount) * 0.1;
        $lineTotal = ($price - $discount + $tax) * $quantity;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'price' => $price,
            'quantity' => $quantity,
            'discount' => $discount,
            'tax' => $tax,
            'line_total' => $lineTotal,
        ];
    }

    /**
     * Sale item with no discount
     */
    public function noDiscount(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => 0,
        ]);
    }

    /**
     * Sale item with high discount
     */
    public function highDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            $discount = $price * 0.5;
            $tax = ($price - $discount) * 0.1;
            $lineTotal = ($price - $discount + $tax) * $attributes['quantity'];

            return [
                'discount' => $discount,
                'tax' => $tax,
                'line_total' => $lineTotal,
            ];
        });
    }

    /**
     * Sale item with large quantity
     */
    public function bulkQuantity(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => fake()->numberBetween(10, 50),
        ]);
    }
}