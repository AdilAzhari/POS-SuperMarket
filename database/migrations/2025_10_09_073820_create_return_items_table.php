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
        Schema::create('return_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained('sale_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Snapshot fields from original sale item
            $table->string('product_name');
            $table->string('sku');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity_returned');
            $table->unsignedInteger('original_quantity');
            $table->decimal('line_total', 12, 2);

            $table->text('condition_notes')->nullable();

            $table->timestamps();
            $table->index(['return_id', 'sale_item_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
