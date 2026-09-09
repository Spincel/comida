<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Obtiene la clave API de Gemini configurada.
     * Si la fila en la BD existe, respeta su valor (incluyendo si fue vaciada intencionalmente).
     */
    public static function getGeminiApiKey(): ?string
    {
        $setting = static::where('key', 'gemini_api_key')->first();
        if ($setting) {
            $val = trim($setting->value ?? '');
            return $val !== '' ? $val : null;
        }

        $envKey = trim((string)(env('GEMINI_API_KEY') ?: config('app.gemini_api_key')));
        return $envKey !== '' ? $envKey : null;
    }

    /**
     * Determina si la IA de Gemini está habilitada con una clave válida.
     */
    public static function hasGeminiApiKey(): bool
    {
        return !empty(static::getGeminiApiKey());
    }
}

