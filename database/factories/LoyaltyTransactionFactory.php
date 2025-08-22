<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoyaltyTransactionFactory extends Factory
{
    protected $model = LoyaltyTransaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['earned', 'redeemed', 'expired', 'adjustment']);

        return [
            'customer_id' => Customer::factory(),
            'sale_id' => $type === 'earned' ? Sale::factory() : null,
            'type' => $type,
            'points' => $this->getPointsForType($type),
            'description' => $this->getDescriptionForType($type),
            'expires_at' => $type === 'earned' ? $this->faker->dateTimeBetween('now', '+1 year') : null,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    private function getPointsForType(string $type): int
    {
        return match ($type) {
            'earned' => $this->faker->numberBetween(1, 500),
            'redeemed' => -$this->faker->numberBetween(10, 200),
            'expired' => -$this->faker->numberBetween(1, 100),
            'adjustment' => $this->faker->randomElement([
                $this->faker->numberBetween(1, 50),
                -$this->faker->numberBetween(1, 50),
            ]),
        };
    }

    private function getDescriptionForType(string $type): string
    {
        return match ($type) {
            'earned' => $this->faker->randomElement([
                'Points earned from purchase',
                'Welcome bonus points',
                'Birthday bonus points',
                'Referral bonus points',
            ]),
            'redeemed' => $this->faker->randomElement([
                'Redeemed: 10% discount',
                'Redeemed: Free shipping',
                'Redeemed: $5 off coupon',
                'Redeemed: Free product',
            ]),
            'expired' => 'Points expired after 1 year',
            'adjustment' => $this->faker->randomElement([
                'Manual adjustment - customer service',
                'System correction',
                'Goodwill gesture',
                'Promotional bonus',
            ]),
        };
    }

    public function earned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'points' => $this->faker->numberBetween(1, 500),
            'description' => 'Points earned from purchase',
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
        ]);
    }

    public function redeemed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'redeemed',
            'points' => -$this->faker->numberBetween(10, 200),
            'description' => 'Redeemed: '.$this->faker->randomElement(['10% discount', 'Free shipping', '$5 off']),
            'expires_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expired',
            'points' => -$this->faker->numberBetween(1, 100),
            'description' => 'Points expired after 1 year',
            'expires_at' => null,
            'created_at' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
        ]);
    }

    public function adjustment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjustment',
            'points' => $this->faker->randomElement([
                $this->faker->numberBetween(1, 50),
                -$this->faker->numberBetween(1, 50),
            ]),
            'description' => 'Manual adjustment - customer service',
            'expires_at' => null,
        ]);
    }
}
