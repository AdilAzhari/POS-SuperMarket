<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $cardBrands = ['visa', 'mastercard', 'amex'];
        $currencies = ['MYR', 'USD', 'SGD'];

        $amount = $this->faker->randomFloat(2, 5, 1000);
        $fee = $amount > 100 ? $this->faker->randomFloat(2, 1, 10) : 0;

        return [
            'sale_id' => Sale::factory(),
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'status' => $this->faker->randomElement(PaymentStatus::cases()),
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $amount - $fee,
            'currency' => $this->faker->randomElement($currencies),
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway_reference' => 'REF-' . $this->faker->numerify('##########'),
            'gateway_response' => [
                'response_code' => '00',
                'response_message' => 'Success',
                'transaction_time' => now()->toISOString(),
            ],
            'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'processed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'processed_at' => null,
        ]);
    }

    /**
     * Indicate that the payment has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'processed_at' => null,
            'notes' => 'Payment failed: ' . $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the payment is a cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::CASH,
            'fee' => 0,
            'net_amount' => $attributes['amount'],
            'gateway_transaction_id' => null,
            'gateway_reference' => null,
            'gateway_response' => null,
            'card_last_four' => null,
            'card_brand' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'tng_phone' => null,
            'tng_reference' => null,
        ]);
    }

    /**
     * Indicate that the payment is a card payment.
     */
    public function card(): static
    {
        $cardBrands = ['visa', 'mastercard', 'amex'];
        
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::CARD,
            'card_last_four' => $this->faker->numerify('####'),
            'card_brand' => $this->faker->randomElement($cardBrands),
            'card_exp_month' => $this->faker->numberBetween(1, 12),
            'card_exp_year' => $this->faker->numberBetween(2024, 2030),
            'tng_phone' => null,
            'tng_reference' => null,
        ]);
    }

    /**
     * Indicate that the payment is a TouchNGo payment.
     */
    public function tng(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::TOUCHNGO,
            'tng_phone' => '+60' . $this->faker->numerify('#########'),
            'tng_reference' => 'TNG-' . $this->faker->numerify('##########'),
            'card_last_four' => null,
            'card_brand' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
        ]);
    }

    /**
     * Indicate that the payment is a digital payment.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentMethod::DIGITAL,
            'card_last_four' => null,
            'card_brand' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'tng_phone' => null,
            'tng_reference' => null,
        ]);
    }

    /**
     * Indicate that the payment is a large amount.
     */
    public function largeAmount(): static
    {
        $amount = $this->faker->randomFloat(2, 500, 5000);
        $fee = $this->faker->randomFloat(2, 5, 50);
        
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $amount - $fee,
        ]);
    }

    /**
     * Indicate that the payment is a small amount.
     */
    public function smallAmount(): static
    {
        $amount = $this->faker->randomFloat(2, 1, 50);
        
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
        ]);
    }

    /**
     * Indicate that the payment was made today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}