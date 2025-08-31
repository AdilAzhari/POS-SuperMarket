<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
final class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'po_number' => 'PO-'.date('Y').'-'.mb_str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'store_id' => Store::factory(),
            'created_by' => User::factory(),
            'status' => $this->faker->randomElement(['draft', 'pending', 'ordered', 'partial', 'received']),
            'total_amount' => $this->faker->randomFloat(2, 100, 5000),
            'notes' => $this->faker->optional()->sentence(),
            'ordered_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'expected_delivery_at' => $this->faker->optional()->dateTimeBetween('now', '+14 days'),
            'received_at' => $this->faker->optional()->dateTimeBetween('-10 days', 'now'),
        ];
    }

    /**
     * Indicate that the purchase order is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'draft',
            'ordered_at' => null,
            'received_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase order is ordered.
     */
    public function ordered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'ordered',
            'ordered_at' => $this->faker->dateTimeBetween('-14 days', '-1 day'),
            'received_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase order is received.
     */
    public function received(): static
    {
        $orderedAt = $this->faker->dateTimeBetween('-30 days', '-7 days');

        return $this->state(fn (array $attributes): array => [
            'status' => 'received',
            'ordered_at' => $orderedAt,
            'received_at' => $this->faker->dateTimeBetween($orderedAt, 'now'),
        ]);
    }
}
