<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating suppliers...');

        // Create regular suppliers
        Supplier::factory(20)->create();

        // Create international suppliers
        Supplier::factory(5)->international()->create();

        // Create local suppliers
        Supplier::factory(10)->local()->create();

        // Create suppliers without email
        Supplier::factory(3)->noEmail()->create();

        $this->command->info('Suppliers created successfully.');
    }
}
