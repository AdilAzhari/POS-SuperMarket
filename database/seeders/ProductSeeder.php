<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

final class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating products...');

        $categories = Category::all();
        $suppliers = Supplier::all();
        $stores = Store::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Please run CategorySeeder first.');

            return;
        }

        if ($suppliers->isEmpty()) {
            $this->command->warn('No suppliers found. Please run SupplierSeeder first.');

            return;
        }

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run StoreSeeder first.');

            return;
        }

        // Create regular products
        Product::factory(100)
            ->recycle($categories)
            ->recycle($suppliers)
            ->create()
            ->each(function ($product) use ($stores): void {
                // Attach product to random stores with stock
                $randomStores = $stores->random(fake()->numberBetween(1, 3));
                foreach ($randomStores as $store) {
                    $product->stores()->attach($store->id, [
                        'stock' => fake()->numberBetween(10, 200),
                        'low_stock_threshold' => fake()->numberBetween(5, 20),
                    ]);
                }
            });

        // Create products with various states
        Product::factory(10)
            ->expensive()
            ->recycle($categories)
            ->recycle($suppliers)
            ->create()
            ->each(function ($product) use ($stores): void {
                $randomStores = $stores->random(fake()->numberBetween(1, 2));
                foreach ($randomStores as $store) {
                    $product->stores()->attach($store->id, [
                        'stock' => fake()->numberBetween(5, 50),
                        'low_stock_threshold' => fake()->numberBetween(2, 10),
                    ]);
                }
            });

        Product::factory(20)
            ->cheap()
            ->recycle($categories)
            ->recycle($suppliers)
            ->create()
            ->each(function ($product) use ($stores): void {
                $randomStores = $stores->random(fake()->numberBetween(2, 4));
                foreach ($randomStores as $store) {
                    $product->stores()->attach($store->id, [
                        'stock' => fake()->numberBetween(50, 500),
                        'low_stock_threshold' => fake()->numberBetween(10, 50),
                    ]);
                }
            });

        Product::factory(5)
            ->inactive()
            ->recycle($categories)
            ->recycle($suppliers)
            ->create();

        Product::factory(10)
            ->noBarcode()
            ->recycle($categories)
            ->recycle($suppliers)
            ->create()
            ->each(function ($product) use ($stores): void {
                $randomStores = $stores->random(1);
                foreach ($randomStores as $store) {
                    $product->stores()->attach($store->id, [
                        'stock' => fake()->numberBetween(1, 20),
                        'low_stock_threshold' => fake()->numberBetween(1, 5),
                    ]);
                }
            });

        Product::factory(15)
            ->highStockThreshold()
            ->recycle($categories)
            ->recycle($suppliers)
            ->create()
            ->each(function ($product) use ($stores): void {
                $randomStores = $stores->random(fake()->numberBetween(1, 3));
                foreach ($randomStores as $store) {
                    $product->stores()->attach($store->id, [
                        'stock' => fake()->numberBetween(100, 1000),
                        'low_stock_threshold' => fake()->numberBetween(50, 100),
                    ]);
                }
            });

        $this->command->info('Products created successfully.');
    }
}
