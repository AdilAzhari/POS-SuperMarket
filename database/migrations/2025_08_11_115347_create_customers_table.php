<?php

declare(strict_types=1);

use App\Enums\CustomerStatus;
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
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('address')->nullable();

            $table->unsignedInteger('total_purchases')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->timestamp('last_purchase_at')->nullable();
            $table->enum('status', array_column(CustomerStatus::cases(), 'value'))->default(CustomerStatus::ACTIVE->value);

            $table->timestamps();
            $table->index(['name', 'phone', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
