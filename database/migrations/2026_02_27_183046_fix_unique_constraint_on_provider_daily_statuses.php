<?php

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
        Schema::table('provider_daily_statuses', function (Blueprint $table) {
            // Drop the old unique constraint
            // Note: In SQLite this might be tricky, but we try the standard Laravel way
            try {
                $table->dropUnique(['provider_id', 'date']);
            } catch (\Exception $e) {
                // If it fails, it might be because the index has a different name or doesn't exist
            }
            
            // Add the new composite unique constraint including meal_type
            $table->unique(['provider_id', 'date', 'meal_type'], 'provider_date_meal_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_daily_statuses', function (Blueprint $table) {
            $table->dropUnique('provider_date_meal_unique');
            $table->unique(['provider_id', 'date']);
        });
    }
};
