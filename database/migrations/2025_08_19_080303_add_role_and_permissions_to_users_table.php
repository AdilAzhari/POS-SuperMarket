<?php

declare(strict_types=1);

use App\Enums\UserRole;
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
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', array_column(UserRole::cases(), 'value'))->default(UserRole::CASHIER->value)->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('employee_id')->nullable()->unique()->after('is_active');
            $table->decimal('hourly_rate', 8, 2)->nullable()->after('employee_id');
            $table->json('permissions')->nullable()->after('hourly_rate');
            $table->timestamp('last_login_at')->nullable()->after('permissions');
            $table->string('phone')->nullable()->after('last_login_at');
            $table->text('address')->nullable()->after('phone');
            $table->date('hire_date')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'role',
                'is_active',
                'employee_id',
                'hourly_rate',
                'permissions',
                'last_login_at',
                'phone',
                'address',
                'hire_date',
            ]);
        });
    }
};
