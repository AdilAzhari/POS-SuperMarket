<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// POS System Scheduled Tasks
Schedule::command('pos:generate-daily-sales-report')
    ->dailyAt('06:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Generate daily sales report for all stores')
    ->onSuccess(function () {
        \Log::info('Daily sales report generated successfully');
    })
    ->onFailure(function () {
        \Log::error('Daily sales report generation failed');
    });

Schedule::command('inventory:check-low-stock --send-alerts --update-thresholds')
    ->dailyAt('08:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Check for low stock items, send alerts, and update thresholds')
    ->onSuccess(function () {
        \Log::info('Low stock check and alert sending completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Low stock check failed');
    });

Schedule::command('pos:process-loyalty-points')
    ->dailyAt('02:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Process loyalty points: expire old points, tier upgrades, birthday rewards')
    ->onSuccess(function () {
        \Log::info('Loyalty points processing completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Loyalty points processing failed');
    });

// Weekly tasks
Schedule::command('pos:generate-daily-sales-report --email=manager@possupermarket.com')
    ->weeklyOn(1, '07:00') // Monday at 7 AM
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Weekly sales report via email');

// Monthly tasks
Schedule::command('pos:cleanup-old-data')
    ->monthlyOn(1, '01:00') // First day of month at 1 AM
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Clean up old data and optimize database');

// Hourly tasks during business hours
Schedule::command('inventory:check-low-stock --send-alerts')
    ->hourlyAt(30) // Every hour at 30 minutes past
    ->between('09:00', '18:00')
    ->weekdays()
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Hourly critical stock check during business hours');

// Emergency stock alerts for critical items
Schedule::command('inventory:check-low-stock --send-alerts')
    ->everyTenMinutes()
    ->between('09:00', '22:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->description('Check for critical stock items every 10 minutes during operating hours');
