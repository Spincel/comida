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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, image, color
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed initial values
        $initialSettings = [
            ['key' => 'logo_main', 'value' => 'logo.png', 'type' => 'image', 'description' => 'Logo principal de la aplicación (Sidebar/Login)'],
            ['key' => 'logo_small', 'value' => 'logo.png', 'type' => 'image', 'description' => 'Logo pequeño para mini-sidebar'],
            ['key' => 'logo_report', 'value' => 'logo.png', 'type' => 'image', 'description' => 'Logo que aparece en los reportes PDF'],
            ['key' => 'color_primary_light', 'value' => '#4f46e5', 'type' => 'color', 'description' => 'Color primario en modo claro'],
            ['key' => 'color_primary_dark', 'value' => '#818cf8', 'type' => 'color', 'description' => 'Color primario en modo oscuro'],
            ['key' => 'app_name', 'value' => 'Control Comedor', 'type' => 'string', 'description' => 'Nombre oficial de la aplicación'],
        ];

        foreach ($initialSettings as $setting) {
            DB::table('system_settings')->insert($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
