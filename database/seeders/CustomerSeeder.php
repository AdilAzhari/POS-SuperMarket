<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating customers...');

        // Create regular customers
        Customer::factory(50)->create();

        // Create customers with purchase history
        Customer::factory(30)->withPurchases()->create();

        // Create VIP customers
        Customer::factory(10)->vip()->create();

        // Create inactive customers
        Customer::factory(5)->inactive()->create();

        // Create customers without email
        Customer::factory(8)->noEmail()->create();

        $this->command->info('Customers created successfully.');
    }
}
