<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating stock movements...');

        $products = Product::all();
        $stores = Store::all();
        $users = User::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run StoreSeeder first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        // Create stock in movements
        StockMovement::factory(50)
            ->stockIn()
            ->recycle($products)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create stock out movements
        StockMovement::factory(30)
            ->stockOut()
            ->recycle($products)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create transfer movements
        StockMovement::factory(20)
            ->transfer()
            ->recycle($products)
            ->recycle($users)
            ->create();

        // Create adjustment movements
        StockMovement::factory(15)
            ->adjustment()
            ->recycle($products)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create bulk movements
        StockMovement::factory(10)
            ->bulk()
            ->recycle($products)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create regular movements
        StockMovement::factory(25)
            ->recycle($products)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        $this->command->info('Stock movements created successfully.');
    }
}
