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
        if (!Schema::hasColumn('orders', 'rating')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->tinyInteger('rating')->nullable()->after('activity_performed');
            });
        }

        if (!Schema::hasColumn('provider_daily_statuses', 'rating')) {
            Schema::table('provider_daily_statuses', function (Blueprint $table) {
                $table->decimal('rating', 3, 2)->nullable()->after('evidence_image');
            });
        }

        if (!Schema::hasColumn('area_session_statuses', 'rating')) {
            Schema::table('area_session_statuses', function (Blueprint $table) {
                $table->tinyInteger('rating')->nullable()->after('evidence_image');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'rating')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }

        if (Schema::hasColumn('provider_daily_statuses', 'rating')) {
            Schema::table('provider_daily_statuses', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }

        if (Schema::hasColumn('area_session_statuses', 'rating')) {
            Schema::table('area_session_statuses', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }
    }
};
