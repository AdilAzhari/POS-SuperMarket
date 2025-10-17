<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use Illuminate\Database\Seeder;
use Random\RandomException;

final class LoyaltyTransactionSeeder extends Seeder
{
    /**
     * @throws RandomException
     */
    public function run(): void
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Please run CustomerSeeder first.');

            return;
        }

        // Create loyalty transactions for each customer
        foreach ($customers as $customer) {
            // Reset customer loyalty points
            $customer->update(['loyalty_points' => 0]);

            $totalEarned = 0;
            $totalRedeemed = 0;

            // Create earned points transactions (from purchases)
            $earnedTransactions = random_int(3, 15);

            for ($i = 0; $i < $earnedTransactions; $i++) {
                $points = random_int(10, 200);
                $totalEarned += $points;

                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'sale_id' => Sale::where('customer_id', $customer->id)->inRandomOrder()->first()?->id,
                    'type' => 'earned',
                    'points' => $points,
                    'description' => $this->getEarnedDescription($points),
                    'expires_at' => now()->addYear(),
                    'created_at' => fake()->dateTimeBetween('-1 year'),
                ]);
            }

            // Create some redeemed points transactions
            if ($totalEarned > 100) {
                $redemptionCount = random_int(1, min(3,(int) floor($totalEarned / 100)));

                for ($i = 0; $i < $redemptionCount; $i++) {
                    $points = -random_int(50, min(200, $totalEarned - (int) $totalRedeemed));
                    $totalRedeemed += abs($points);

                    LoyaltyTransaction::create([
                        'customer_id' => $customer->id,
                        'type' => 'redeemed',
                        'points' => $points,
                        'description' => $this->getRedeemedDescription(),
                        'created_at' => fake()->dateTimeBetween('-6 months'),
                    ]);
                }
            }

            // Create some expired points (random chance)
            if (random_int(1, 100) <= 30) { // 30% chance
                $expiredPoints = -random_int(10, min(50, $totalEarned));
                $totalRedeemed += abs($expiredPoints);

                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'expired',
                    'points' => $expiredPoints,
                    'description' => 'Points expired after 1 year',
                    'created_at' => fake()->dateTimeBetween('-2 months'),
                ]);
            }

            // Create some manual adjustments (rare)
            if (random_int(1, 100) <= 15) { // 15% chance
                $adjustmentPoints = random_int(1, 2) === 1 ? random_int(10, 50) : -random_int(5, 25);

                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'adjustment',
                    'points' => $adjustmentPoints,
                    'description' => $this->getAdjustmentDescription($adjustmentPoints > 0),
                    'created_at' => fake()->dateTimeBetween('-3 months'),
                ]);

                if ($adjustmentPoints < 0) {
                    $totalRedeemed += abs($adjustmentPoints);
                } else {
                    $totalEarned += $adjustmentPoints;
                }
            }

            // Update customer's final loyalty points
            $finalPoints = $totalEarned - $totalRedeemed;
            $customer->update(['loyalty_points' => max(0, $finalPoints)]);
        }

        // Create some birthday bonus transactions for customers with birthdays
        $birthdayCustomers = Customer::whereNotNull('birthday')->get();
        foreach ($birthdayCustomers as $customer) {
            // Check if birthday was in the last year
            $birthdayThisYear = $customer->birthday->year(now()->year);
            if ($birthdayThisYear->isPast() && $birthdayThisYear->diffInDays(now()) <= 365) {
                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'earned',
                    'points' => 100, // Birthday bonus
                    'description' => 'Happy Birthday! Birthday bonus points',
                    'expires_at' => now()->addYear(),
                    'created_at' => $birthdayThisYear->addHours(10), // Around 10 AM on birthday
                ]);

                $customer->increment('loyalty_points', 100);
            }
        }

        $this->command->info('Created '.LoyaltyTransaction::count().' loyalty transactions');
        $this->command->info('Updated loyalty points for '.$customers->count().' customers');
    }

    /**
     * @throws RandomException
     */
    private function getEarnedDescription(int $points): string
    {
        $descriptions = [
            'Points earned from purchase ($'.number_format($points / 10, 2).')',
            'Welcome bonus points',
            'Points earned from shopping',
            'Purchase reward points',
            'Shopping bonus',
        ];

        // Most transactions should be from purchases
        return random_int(1, 100) <= 70
            ? 'Points earned from purchase ($'.number_format($points / 10, 2).')'
            : fake()->randomElement(array_slice($descriptions, 1));
    }

    private function getRedeemedDescription(): string
    {
        return fake()->randomElement([
            'Redeemed: 10% discount',
            'Redeemed: $5 off coupon',
            'Redeemed: Free shipping',
            'Redeemed: $10 discount',
            'Redeemed: 15% off purchase',
            'Redeemed: Free product',
        ]);
    }

    private function getAdjustmentDescription(bool $isPositive): string
    {
        if ($isPositive) {
            return fake()->randomElement([
                'Goodwill gesture - customer service',
                'Compensation points',
                'Promotional bonus',
                'Manual adjustment - positive',
                'Customer satisfaction bonus',
            ]);
        }

        return fake()->randomElement([
            'Points correction - customer service',
            'System adjustment',
            'Manual correction',
            'Point balance fix',
        ]);
    }
}
