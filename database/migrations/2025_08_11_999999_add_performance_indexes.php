<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['active', 'created_at']);
            $table->index(['category_id', 'active']);
            $table->index(['supplier_id', 'active']);
            $table->index(['sku']);
            $table->index(['barcode']);
            $table->index(['name']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->index(['store_id', 'created_at']);
            $table->index(['customer_id', 'created_at']);
            $table->index(['cashier_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['payment_method']);
            $table->index(['paid_at']);
            $table->index(['code']);
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->index(['product_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->index(['email']);
            $table->index(['phone']);
            $table->index(['last_purchase_at']);
            $table->index(['total_spent']);
        });

        Schema::table('product_store', function (Blueprint $table): void {
            $table->index(['product_id', 'store_id']);
            $table->index(['store_id', 'stock']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->index(['product_id', 'created_at']);
            $table->index(['store_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index(['name']);
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['active', 'created_at']);
            $table->dropIndex(['category_id', 'active']);
            $table->dropIndex(['supplier_id', 'active']);
            $table->dropIndex(['sku']);
            $table->dropIndex(['barcode']);
            $table->dropIndex(['name']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropIndex(['store_id', 'created_at']);
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['cashier_id', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['code']);
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex(['product_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['last_purchase_at']);
            $table->dropIndex(['total_spent']);
        });

        Schema::table('product_store', function (Blueprint $table): void {
            $table->dropIndex(['product_id', 'store_id']);
            $table->dropIndex(['store_id', 'stock']);
        });

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['product_id', 'created_at']);
            $table->dropIndex(['store_id', 'created_at']);
            $table->dropIndex(['type', 'created_at']);
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });
    }
};
