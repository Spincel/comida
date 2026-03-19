<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reportDefaults = json_encode([
            'show_avatar' => true,
            'show_area' => true,
            'show_platillo' => true,
            'show_preferences' => true,
            'show_activity' => true,
            'show_signature' => true,
            'default_sort' => 'area',
            'font_size' => '10px'
        ]);

        $whatsappDefaults = json_encode([
            'include_names' => true,
            'group_by_dish' => true,
            'footer_text' => 'Generado desde Sistema Comedor'
        ]);

        DB::table('system_settings')->insert([
            [
                'key' => 'report_configuration',
                'value' => $reportDefaults,
                'type' => 'json',
                'description' => 'Configuración visual de los reportes PDF/Excel/Word',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_configuration',
                'value' => $whatsappDefaults,
                'type' => 'json',
                'description' => 'Configuración de los mensajes de WhatsApp',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['report_configuration', 'whatsapp_configuration'])->delete();
    }
};
