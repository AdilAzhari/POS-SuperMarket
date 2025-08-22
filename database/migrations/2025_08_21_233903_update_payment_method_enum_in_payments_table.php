<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing data to match new enum values
        DB::statement("UPDATE payments SET payment_method = 'card' WHERE payment_method IN ('visa', 'mastercard', 'amex', 'stripe')");

        // Drop the index first, then the column
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method', 'status']);
            $table->dropColumn('payment_method');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'digital', 'bank_transfer', 'tng', 'grab_pay', 'mobile_payment'])->after('user_id');
            $table->index(['payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method', 'status']);
            $table->dropColumn('payment_method');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'stripe', 'visa', 'tng', 'mastercard', 'amex'])->after('user_id');
            $table->index(['payment_method', 'status']);
        });
    }
};
