<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
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
        $subtotal = fake()->randomFloat(2, 10, 500);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $tax = ($subtotal - $discount) * 0.1;
        $total = $subtotal - $discount + $tax;

        return [
            'code' => strtoupper(fake()->bothify('TXN-######')),
            'store_id' => Store::factory(),
            'customer_id' => fake()->optional(0.7)->randomElement([Customer::factory()]),
            'cashier_id' => User::factory(),
            'items_count' => fake()->numberBetween(1, 10),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'payment_method' => fake()->randomElement(['cash', 'card', 'mobile_payment']),
            'status' => 'completed',
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Sale with no customer (walk-in)
     */
    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => null,
        ]);
    }

    /**
     * Large sale
     */
    public function large(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = fake()->randomFloat(2, 1000, 5000);
            $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
            $tax = ($subtotal - $discount) * 0.1;
            $total = $subtotal - $discount + $tax;

            return [
                'items_count' => fake()->numberBetween(20, 50),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
            ];
        });
    }

    /**
     * Sale with high discount
     */
    public function discounted(): static
    {
        return $this->state(function (array $attributes) {
            $subtotal = $attributes['subtotal'];
            $discount = $subtotal * 0.3;
            $tax = ($subtotal - $discount) * 0.1;
            $total = $subtotal - $discount + $tax;

            return [
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
            ];
        });
    }

    /**
     * Pending sale
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    /**
     * Card payment
     */
    public function cardPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'card',
        ]);
    }

    /**
     * Today's sale
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_at' => fake()->dateTimeBetween('today', 'now'),
        ]);
    }
}
