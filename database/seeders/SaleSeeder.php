<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

final class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sales...');

        $stores = Store::all();
        $customers = Customer::all();
        $users = User::all();

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run StoreSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');

            return;
        }

        // Create regular sales
        Sale::factory(100)
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        // Create walk-in sales (no customer)
        Sale::factory(50)
            ->walkIn()
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create large sales
        Sale::factory(10)
            ->large()
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        // Create discounted sales
        Sale::factory(20)
            ->discounted()
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        // Create pending sales
        Sale::factory(5)
            ->pending()
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        // Create card payment sales
        Sale::factory(30)
            ->cardPayment()
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        // Create today's sales
        Sale::factory(15)
            ->today()
            ->recycle($stores)
            ->recycle($customers)
            ->recycle($users)
            ->create();

        $this->command->info('Sales created successfully.');
    }
}
