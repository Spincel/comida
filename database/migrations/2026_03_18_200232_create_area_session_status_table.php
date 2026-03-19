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
        Schema::create('area_session_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_daily_status_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->string('evidence_image')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed
            $table->timestamps();
            
            $table->unique(['provider_daily_status_id', 'area_id'], 'area_session_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_session_statuses');
    }
};
