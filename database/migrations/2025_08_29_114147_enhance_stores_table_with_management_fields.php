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
        Schema::table('stores', function (Blueprint $table): void {
            // Add manager relationship
            if (! Schema::hasColumn('stores', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->after('email');
            }

            // Add store status
            if (! Schema::hasColumn('stores', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('manager_id');
            }

            // Add operating hours as JSON
            if (! Schema::hasColumn('stores', 'operating_hours')) {
                $table->json('operating_hours')->nullable()->after('is_active');
            }

            // Add timezone
            if (! Schema::hasColumn('stores', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('operating_hours');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropForeign(['manager_id']);
            $table->dropColumn([
                'manager_id',
                'is_active',
                'operating_hours',
                'timezone',
            ]);
        });
    }
};
