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
        Schema::table('daily_menus', function (Blueprint $blueprint) {
            $blueprint->string('ai_source_image')->nullable()->after('provider_id')->comment('Path to the image analyzed by AI');
            // We don't drop available_on to maintain historical data if needed, 
            // but we'll make it nullable if it isn't already to allow perpetual items.
            $blueprint->date('available_on')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_menus', function (Blueprint $blueprint) {
            $blueprint->dropColumn('ai_source_image');
            $blueprint->date('available_on')->nullable(false)->change();
        });
    }
};
