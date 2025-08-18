<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique(); // e.g., PAY-000001
            
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('payment_method', ['cash', 'stripe', 'visa', 'tng', 'mastercard', 'amex']);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0); // Payment gateway fees
            $table->decimal('net_amount', 10, 2); // Amount after fees
            
            $table->string('currency', 3)->default('MYR');
            
            // External payment gateway data
            $table->string('gateway_transaction_id')->nullable(); // Stripe payment intent ID, etc.
            $table->string('gateway_reference')->nullable(); // Bank reference, TNG reference, etc.
            $table->json('gateway_response')->nullable(); // Full gateway response for debugging
            
            // Customer payment details (for card payments)
            $table->string('card_last_four')->nullable();
            $table->string('card_brand')->nullable(); // visa, mastercard, amex
            $table->string('card_exp_month')->nullable();
            $table->string('card_exp_year')->nullable();
            
            // TNG specific fields
            $table->string('tng_phone')->nullable();
            $table->string('tng_reference')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['payment_method', 'status']);
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
