<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LoyaltyReward;
use App\Models\Product;
use Illuminate\Database\Seeder;

final class LoyaltyRewardSeeder extends Seeder
{
    public function run(): void
    {
        // Create default loyalty rewards
        $rewards = [
            [
                'name' => '5% Off Next Purchase',
                'description' => 'Get 5% discount on your next purchase of any amount',
                'type' => 'percentage_discount',
                'discount_value' => 5.00,
                'points_required' => 100,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => '10% Off Next Purchase',
                'description' => 'Get 10% discount on your next purchase of any amount',
                'type' => 'percentage_discount',
                'discount_value' => 10.00,
                'points_required' => 200,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => '15% Off Next Purchase',
                'description' => 'Get 15% discount on your next purchase of any amount',
                'type' => 'percentage_discount',
                'discount_value' => 15.00,
                'points_required' => 350,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => '$5 Off Purchase',
                'description' => 'Get $5 off your next purchase of $25 or more',
                'type' => 'fixed_discount',
                'discount_value' => 5.00,
                'points_required' => 75,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => '$10 Off Purchase',
                'description' => 'Get $10 off your next purchase of $50 or more',
                'type' => 'fixed_discount',
                'discount_value' => 10.00,
                'points_required' => 150,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => '$20 Off Purchase',
                'description' => 'Get $20 off your next purchase of $100 or more',
                'type' => 'fixed_discount',
                'discount_value' => 20.00,
                'points_required' => 300,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => 'Free Shipping',
                'description' => 'Get free shipping on your next order',
                'type' => 'free_shipping',
                'discount_value' => 0,
                'points_required' => 50,
                'is_active' => true,
                'usage_limit' => null,
            ],
            [
                'name' => 'Free Express Shipping',
                'description' => 'Get free express shipping on your next order',
                'type' => 'free_shipping',
                'discount_value' => 0,
                'points_required' => 100,
                'is_active' => true,
                'usage_limit' => null,
            ],
        ];

        foreach ($rewards as $reward) {
            LoyaltyReward::create($reward);
        }

        // Create some limited-time and special rewards
        $specialRewards = [
            [
                'name' => 'Birthday Special - 20% Off',
                'description' => 'Special birthday discount - 20% off entire purchase',
                'type' => 'percentage_discount',
                'discount_value' => 20.00,
                'points_required' => 250,
                'is_active' => true,
                'valid_from' => now()->subDays(30),
                'valid_until' => now()->addDays(60),
                'usage_limit' => 1000,
            ],
            [
                'name' => 'VIP Member - 25% Off',
                'description' => 'Exclusive VIP member discount - 25% off entire purchase',
                'type' => 'percentage_discount',
                'discount_value' => 25.00,
                'points_required' => 500,
                'is_active' => true,
                'usage_limit' => 500,
            ],
            [
                'name' => 'Flash Sale - $15 Off',
                'description' => 'Limited time flash sale discount',
                'type' => 'fixed_discount',
                'discount_value' => 15.00,
                'points_required' => 200,
                'is_active' => true,
                'valid_from' => now(),
                'valid_until' => now()->addDays(7),
                'usage_limit' => 100,
            ],
        ];

        foreach ($specialRewards as $reward) {
            LoyaltyReward::create($reward);
        }

        // Create some free product rewards if products exist
        if (Product::count() > 0) {
            $products = Product::inRandomOrder()->limit(3)->get();

            foreach ($products as $product) {
                LoyaltyReward::create([
                    'name' => "Free {$product->name}",
                    'description' => "Get a free {$product->name} with your purchase",
                    'type' => 'free_product',
                    'discount_value' => 0,
                    'points_required' => (int) ($product->price * 20), // 20 points per dollar of product value
                    'free_product_id' => $product->id,
                    'is_active' => true,
                    'usage_limit' => 50,
                ]);
            }
        }

        // Create some inactive/expired rewards for testing
        $inactiveRewards = [
            [
                'name' => 'Expired Summer Sale',
                'description' => 'Summer sale discount that has expired',
                'type' => 'percentage_discount',
                'discount_value' => 30.00,
                'points_required' => 400,
                'is_active' => false,
                'valid_until' => now()->subDays(30),
                'usage_limit' => 200,
                'times_used' => 150,
            ],
            [
                'name' => 'Sold Out Free Product',
                'description' => 'Free product that is no longer available',
                'type' => 'free_product',
                'discount_value' => 0,
                'points_required' => 300,
                'is_active' => false,
                'usage_limit' => 50,
                'times_used' => 50,
            ],
        ];

        foreach ($inactiveRewards as $reward) {
            LoyaltyReward::create($reward);
        }

        $this->command->info('Created '.LoyaltyReward::count().' loyalty rewards');
    }
}
