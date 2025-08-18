<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_email' => fake()->companyEmail(),
            'address' => fake()->address(),
        ];
    }

    /**
     * International supplier
     */
    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->company() . ' International',
            'contact_phone' => '+' . fake()->numberBetween(1, 999) . ' ' . fake()->phoneNumber(),
        ]);
    }

    /**
     * Local supplier
     */
    public function local(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->city() . ' ' . fake()->companySuffix(),
        ]);
    }

    /**
     * Supplier without email
     */
    public function noEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'contact_email' => null,
        ]);
    }
}
