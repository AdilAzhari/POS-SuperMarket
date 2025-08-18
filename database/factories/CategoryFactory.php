<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Electronics', 'Clothing', 'Food & Beverages', 'Home & Garden', 
            'Health & Beauty', 'Sports & Outdoors', 'Books & Media', 'Toys & Games'
        ];
        
        $name = fake()->randomElement($categories) . ' ' . fake()->word();

        return [
            'name' => $name,
            'slug' => str($name)->slug(),
        ];
    }

    /**
     * Electronics category
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);
    }

    /**
     * Food category
     */
    public function food(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Food & Beverages',
            'slug' => 'food-beverages',
        ]);
    }

    /**
     * Clothing category
     */
    public function clothing(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Clothing',
            'slug' => 'clothing',
        ]);
    }
}
