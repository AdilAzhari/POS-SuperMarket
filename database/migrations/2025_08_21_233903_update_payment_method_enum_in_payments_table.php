<?php

declare(strict_types=1);

use App\Enums\PaymentMethod;
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
        DB::statement("UPDATE payments SET payment_method = '".PaymentMethod::CARD->value."' WHERE payment_method IN ('visa', 'mastercard', 'amex', 'stripe')");

        // Drop the index first, then the column
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['payment_method', 'status']);
            $table->dropColumn('payment_method');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('payment_method', array_column(PaymentMethod::cases(), 'value'))->after('user_id');
            $table->index(['payment_method', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['payment_method', 'status']);
            $table->dropColumn('payment_method');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'stripe', 'visa', 'tng', 'mastercard', 'amex'])->after('user_id');
            $table->index(['payment_method', 'status']);
        });
    }
};
