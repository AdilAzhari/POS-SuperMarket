<?php

declare(strict_types=1);

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
        Schema::create('returns', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique(); // e.g. RET-001

            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('processed_by')->constrained('users')->cascadeOnDelete();

            $table->enum('reason', [
                'defective',
                'wrong_item',
                'customer_change_mind',
                'damaged_shipping',
                'not_as_described',
                'duplicate_order',
                'other',
            ]);

            $table->enum('refund_method', [
                'original_payment',
                'cash',
                'store_credit',
                'exchange',
            ]);

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_refund', 12, 2)->default(0);
            $table->decimal('total_refund', 12, 2)->default(0);

            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
            $table->index(['sale_id', 'store_id', 'customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
