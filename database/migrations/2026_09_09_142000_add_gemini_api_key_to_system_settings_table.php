<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SystemSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existing = SystemSetting::where('key', 'gemini_api_key')->first();
        if (!$existing) {
            $envKey = env('GEMINI_API_KEY');
            SystemSetting::create([
                'key' => 'gemini_api_key',
                'value' => !empty($envKey) ? $envKey : null,
                'type' => 'string',
                'description' => 'Clave API de Google Gemini para reconocimiento óptico de menús y extracción de documentos'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SystemSetting::where('key', 'gemini_api_key')->delete();
    }
};
