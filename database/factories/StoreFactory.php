<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Store',
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
        ];
    }

    /**
     * Main store
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main Store',
        ]);
    }

    /**
     * Branch store
     */
    public function branch(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->city() . ' Branch',
        ]);
    }

    /**
     * Outlet store
     */
    public function outlet(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->streetName() . ' Outlet',
        ]);
    }
}
