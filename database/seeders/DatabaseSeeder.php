<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        //         Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@supermarket.com',
            'role' => UserRole::ADMIN,
            'password' => bcrypt('supermarket'),
        ]);

        //         Create additional users
        User::factory(5)->create();

        $this->command->info('👤 Users created');

        // Seed in proper order (respecting foreign key dependencies)
        $this->call([
            SettingSeeder::class,         // No dependencies
            CategorySeeder::class,        // No dependencies
            SupplierSeeder::class,        // No dependencies
            StoreSeeder::class,           // No dependencies
            CustomerSeeder::class,        // No dependencies
            ProductSeeder::class,         // Depends on: categories, suppliers
            SaleSeeder::class,            // Depends on: stores, customers, users
            SaleItemSeeder::class,        // Depends on: sales, products
            PaymentSeeder::class,         // Depends on: sales, stores, users
            StockMovementSeeder::class,   // Depends on: products, stores, users

            // Loyalty program seeders
            LoyaltyRewardSeeder::class,       // Depends on: products (for free product rewards)
            LoyaltyTransactionSeeder::class,  // Depends on: customers, sales
            RewardRedemptionSeeder::class,    // Depends on: customers, loyalty_rewards, sales
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
    }
}
