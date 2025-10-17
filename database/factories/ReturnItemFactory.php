<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReturnItem>
 */
final class ReturnItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 100);
        $originalQuantity = fake()->numberBetween(1, 10);
        $quantityReturned = fake()->numberBetween(1, $originalQuantity);
        $lineTotal = $price * $quantityReturned;

        return [
            'return_id' => ProductReturn::factory(),
            'sale_item_id' => SaleItem::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'sku' => fake()->unique()->regexify('[A-Z]{3}[0-9]{5}'),
            'price' => $price,
            'quantity_returned' => $quantityReturned,
            'original_quantity' => $originalQuantity,
            'line_total' => $lineTotal,
            'condition_notes' => fake()->optional(0.5)->sentence(),
        ];
    }
}
