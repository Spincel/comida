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
        if (!Schema::hasColumn('provider_daily_statuses', 'meal_type')) {
            Schema::table('provider_daily_statuses', function (Blueprint $table) {
                $table->string('meal_type')->after('date')->default('Comida');
            });
        }
        
        if (!Schema::hasColumn('orders', 'meal_type')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('meal_type')->after('daily_menu_id')->default('Comida');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed for a fix
    }
};
