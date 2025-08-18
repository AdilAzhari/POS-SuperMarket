<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@supermarket.com',
        ]);

        // Create additional users
        User::factory(5)->create();

        $this->command->info('👤 Users created');

        // Seed in proper order (respecting foreign key dependencies)
        $this->call([
            SettingSeeder::class,      // No dependencies
            CategorySeeder::class,     // No dependencies
            SupplierSeeder::class,     // No dependencies
            StoreSeeder::class,        // No dependencies
            CustomerSeeder::class,     // No dependencies
            ProductSeeder::class,      // Depends on: categories, suppliers
            SaleSeeder::class,         // Depends on: stores, customers, users
            SaleItemSeeder::class,     // Depends on: sales, products
            StockMovementSeeder::class, // Depends on: products, stores, users
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
    }
}
