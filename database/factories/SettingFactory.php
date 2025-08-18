<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settingTypes = [
            'app_name' => ['SuperMarket POS'],
            'currency' => ['USD', 'EUR', 'GBP'],
            'tax_rate' => [10, 15, 20],
            'theme' => ['light', 'dark'],
            'notifications' => [true, false],
        ];

        $key = fake()->randomElement(array_keys($settingTypes));
        $value = fake()->randomElement($settingTypes[$key]);

        return [
            'key' => $key,
            'value' => $value,
        ];
    }

    /**
     * App configuration setting
     */
    public function appConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'app_name',
            'value' => 'SuperMarket POS System',
        ]);
    }

    /**
     * Tax setting
     */
    public function taxSetting(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'tax_rate',
            'value' => fake()->randomFloat(2, 5, 25),
        ]);
    }

    /**
     * Currency setting
     */
    public function currency(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'currency',
            'value' => fake()->randomElement(['USD', 'EUR', 'GBP', 'JPY']),
        ]);
    }

    /**
     * Notification setting
     */
    public function notification(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'email_notifications',
            'value' => fake()->boolean(),
        ]);
    }

    /**
     * JSON configuration setting
     */
    public function jsonConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'payment_methods',
            'value' => ['cash', 'card', 'mobile_payment'],
        ]);
    }
}
