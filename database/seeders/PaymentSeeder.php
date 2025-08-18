<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating payments...');

        $sales = Sale::all();
        $stores = Store::all();
        $users = User::all();

        if ($sales->isEmpty()) {
            $this->command->warn('No sales found. Please run SaleSeeder first.');
            return;
        }

        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Please run StoreSeeder first.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please create users first.');
            return;
        }

        // Create completed cash payments
        Payment::factory(80)
            ->completed()
            ->cash()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create completed card payments
        Payment::factory(60)
            ->completed()
            ->card()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create TouchNGo payments
        Payment::factory(40)
            ->completed()
            ->tng()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create digital payments
        Payment::factory(30)
            ->completed()
            ->digital()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create pending payments
        Payment::factory(10)
            ->pending()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create failed payments
        Payment::factory(5)
            ->failed()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create large amount payments
        Payment::factory(15)
            ->completed()
            ->largeAmount()
            ->card()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create small amount payments
        Payment::factory(20)
            ->completed()
            ->smallAmount()
            ->cash()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        // Create today's payments
        Payment::factory(25)
            ->completed()
            ->today()
            ->recycle($sales)
            ->recycle($stores)
            ->recycle($users)
            ->create();

        $this->command->info('Payments created successfully.');
    }
}