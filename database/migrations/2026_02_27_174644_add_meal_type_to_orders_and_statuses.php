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
            $table->string('meal_type')->default('Comida')->after('date');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('meal_type')->default('Comida')->after('daily_menu_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_daily_statuses', function (Blueprint $table) {
            $table->dropColumn('meal_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('meal_type');
        });
    }
};
