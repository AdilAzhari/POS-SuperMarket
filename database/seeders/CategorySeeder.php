<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating categories...');

        // Create predefined main categories
        $mainCategories = [
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Food & Beverages', 'slug' => 'food-beverages'],
            ['name' => 'Clothing', 'slug' => 'clothing'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden'],
            ['name' => 'Health & Beauty', 'slug' => 'health-beauty'],
            ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors'],
            ['name' => 'Books & Media', 'slug' => 'books-media'],
            ['name' => 'Toys & Games', 'slug' => 'toys-games'],
        ];

        foreach ($mainCategories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['slug' => $category['slug']]
            );
        }

        // Create additional random categories using factories
        Category::factory(15)->create();

        $this->command->info('Categories created successfully.');
    }
}
