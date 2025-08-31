<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating settings...');

        // Create essential app settings
        $essentialSettings = [
            ['key' => 'app_name', 'value' => 'SuperMarket POS'],
            ['key' => 'currency', 'value' => 'USD'],
            ['key' => 'tax_rate', 'value' => 10.0],
            ['key' => 'timezone', 'value' => 'UTC'],
            ['key' => 'date_format', 'value' => 'Y-m-d'],
            ['key' => 'time_format', 'value' => 'H:i:s'],
            ['key' => 'language', 'value' => 'en'],
            ['key' => 'theme', 'value' => 'light'],
            ['key' => 'email_notifications', 'value' => true],
            ['key' => 'sms_notifications', 'value' => false],
            ['key' => 'low_stock_alert', 'value' => true],
            ['key' => 'payment_methods', 'value' => ['cash', 'card', 'mobile_payment']],
            ['key' => 'receipt_footer', 'value' => 'Thank you for shopping with us!'],
            ['key' => 'business_address', 'value' => '123 Main Street, City, State, ZIP'],
            ['key' => 'business_phone', 'value' => '+1-555-0123'],
        ];

        foreach ($essentialSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        // Create additional random settings with unique keys
        for ($i = 0; $i < 10; $i++) {
            $key = 'setting_'.$i.'_'.fake()->word();
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => fake()->word()]
            );
        }

        $this->command->info('Settings created successfully.');
    }
}
