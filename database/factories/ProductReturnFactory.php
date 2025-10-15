<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductReturn>
 */
final class ProductReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 200);
        $taxRefund = $subtotal * 0.06;
        $totalRefund = $subtotal + $taxRefund;

        return [
            'code' => mb_strtoupper(fake()->bothify('RET-######')),
            'sale_id' => Sale::factory(),
            'store_id' => Store::factory(),
            'customer_id' => fake()->optional(0.8)->randomElement([Customer::factory()]),
            'processed_by' => User::factory(),
            'reason' => fake()->randomElement([
                'defective',
                'wrong_item',
                'customer_change_mind',
                'damaged_shipping',
                'not_as_described',
                'duplicate_order',
                'other',
            ]),
            'refund_method' => fake()->randomElement([
                'original_payment',
                'cash',
                'store_credit',
                'exchange',
            ]),
            'subtotal' => $subtotal,
            'tax_refund' => $taxRefund,
            'total_refund' => $totalRefund,
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'completed']),
            'notes' => fake()->optional(0.5)->sentence(),
            'processed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Return for a defective product
     */
    public function defective(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => 'defective',
        ]);
    }

    /**
     * Return with store credit refund
     */
    public function storeCredit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'refund_method' => 'store_credit',
        ]);
    }

    /**
     * Completed return
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
        ]);
    }

    /**
     * Pending return
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }
}
