<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->bothify('TXN-######')),
            'store_id' => \App\Models\Store::factory(),
            'customer_id' => null,
            'cashier_id' => \App\Models\User::factory(),
            'items_count' => 0,
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => now(),
        ];
    }
}
