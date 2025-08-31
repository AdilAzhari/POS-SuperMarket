<?php

declare(strict_types=1);

use App\Enums\LoyaltyRewardType;
use App\Enums\LoyaltyTier;
use App\Enums\LoyaltyTransactionType;
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
        // Add loyalty fields to customers table (check if columns don't already exist)
        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'loyalty_points')) {
                $table->integer('loyalty_points')->default(0)->after('status');
            }
            if (! Schema::hasColumn('customers', 'loyalty_tier')) {
                $table->enum('loyalty_tier', array_column(LoyaltyTier::cases(), 'value'))->default(LoyaltyTier::BRONZE->value)->after('loyalty_points');
            }
            if (! Schema::hasColumn('customers', 'birthday')) {
                $table->date('birthday')->nullable()->after('loyalty_tier');
            }
            if (! Schema::hasColumn('customers', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('birthday');
            }
        });

        // Create loyalty transactions table
        Schema::create('loyalty_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', array_column(LoyaltyTransactionType::cases(), 'value'));
            $table->integer('points');
            $table->string('description');
            $table->json('metadata')->nullable(); // Store additional data like expiry date
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });

        // Create loyalty rewards table
        Schema::create('loyalty_rewards', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('points_required');
            $table->enum('type', array_column(LoyaltyRewardType::cases(), 'value'));
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->foreignId('free_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamps();
        });

        // Create customer reward redemptions table
        Schema::create('reward_redemptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('points_used');
            $table->decimal('discount_amount', 8, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_transactions');

        Schema::table('customers', function (Blueprint $table): void {
            if (Schema::hasColumn('customers', 'marketing_consent')) {
                $table->dropColumn('marketing_consent');
            }
            if (Schema::hasColumn('customers', 'birthday')) {
                $table->dropColumn('birthday');
            }
            if (Schema::hasColumn('customers', 'loyalty_tier')) {
                $table->dropColumn('loyalty_tier');
            }
            if (Schema::hasColumn('customers', 'loyalty_points')) {
                $table->dropColumn('loyalty_points');
            }
        });
    }
};
