<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating stores...');

        // Create main store
        Store::factory()->main()->create();

        // Create branch stores
        Store::factory(5)->branch()->create();

        // Create outlet stores
        Store::factory(3)->outlet()->create();

        // Create additional regular stores
        Store::factory(6)->create();

        $this->command->info('Stores created successfully.');
    }
}
