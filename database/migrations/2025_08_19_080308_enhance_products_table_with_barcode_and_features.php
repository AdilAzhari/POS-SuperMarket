<?php

declare(strict_types=1);

use App\Enums\ProductSerialStatus;
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
        Schema::table('products', function (Blueprint $table): void {
            // Barcode and identification (check if not exists)
            if (! Schema::hasColumn('products', 'internal_code')) {
                $table->string('internal_code')->nullable()->unique()->after('barcode');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('cost');
            }

            // Enhanced inventory tracking
            if (! Schema::hasColumn('products', 'track_serial_numbers')) {
                $table->boolean('track_serial_numbers')->default(false)->after('description');
            }
            if (! Schema::hasColumn('products', 'track_batches')) {
                $table->boolean('track_batches')->default(false)->after('track_serial_numbers');
            }
            if (! Schema::hasColumn('products', 'reorder_point')) {
                $table->integer('reorder_point')->default(0)->after('track_batches');
            }
            if (! Schema::hasColumn('products', 'reorder_quantity')) {
                $table->integer('reorder_quantity')->default(0)->after('reorder_point');
            }
            if (! Schema::hasColumn('products', 'max_stock_level')) {
                $table->integer('max_stock_level')->nullable()->after('reorder_quantity');
            }

            // Product characteristics
            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 3)->nullable()->after('max_stock_level');
            }
            if (! Schema::hasColumn('products', 'weight_unit')) {
                $table->string('weight_unit', 10)->default('kg')->after('weight');
            }
            if (! Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 8, 3)->nullable()->after('weight_unit');
            }
            if (! Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 8, 3)->nullable()->after('length');
            }
            if (! Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 8, 3)->nullable()->after('width');
            }
            if (! Schema::hasColumn('products', 'dimension_unit')) {
                $table->string('dimension_unit', 10)->default('cm')->after('height');
            }

            // Business attributes
            if (! Schema::hasColumn('products', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('active');
            }
            if (! Schema::hasColumn('products', 'allow_backorder')) {
                $table->boolean('allow_backorder')->default(false)->after('is_featured');
            }
            if (! Schema::hasColumn('products', 'discontinued_at')) {
                $table->date('discontinued_at')->nullable()->after('allow_backorder');
            }

            // Pricing enhancements
            if (! Schema::hasColumn('products', 'wholesale_price')) {
                $table->decimal('wholesale_price', 8, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'compare_at_price')) {
                $table->decimal('compare_at_price', 8, 2)->nullable()->after('wholesale_price');
            }
            if (! Schema::hasColumn('products', 'tax_exempt')) {
                $table->boolean('tax_exempt')->default(false)->after('compare_at_price');
            }

            // Additional info
            if (! Schema::hasColumn('products', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('tax_exempt');
            }
            if (! Schema::hasColumn('products', 'brand')) {
                $table->string('brand')->nullable()->after('manufacturer');
            }
            if (! Schema::hasColumn('products', 'notes')) {
                $table->text('notes')->nullable()->after('brand');
            }
            if (! Schema::hasColumn('products', 'tags')) {
                $table->json('tags')->nullable()->after('notes');
            }
        });

        // Create product batches table for batch tracking
        Schema::create('product_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('batch_number');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity');
            $table->decimal('cost_price', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'batch_number']);
            $table->index(['product_id', 'expiry_date']);
        });

        // Create product serial numbers table
        Schema::create('product_serial_numbers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('serial_number')->unique();
            $table->enum('status', array_column(ProductSerialStatus::cases(), 'value'))->default(ProductSerialStatus::AVAILABLE->value);
            $table->foreignId('sale_item_id')->nullable()->constrained()->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serial_numbers');
        Schema::dropIfExists('product_batches');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'barcode',
                'internal_code',
                'track_serial_numbers',
                'track_batches',
                'reorder_point',
                'reorder_quantity',
                'max_stock_level',
                'weight',
                'weight_unit',
                'length',
                'width',
                'height',
                'dimension_unit',
                'is_active',
                'is_featured',
                'allow_backorder',
                'discontinued_at',
                'wholesale_price',
                'compare_at_price',
                'tax_exempt',
                'manufacturer',
                'brand',
                'notes',
                'tags',
            ]);
        });
    }
};
