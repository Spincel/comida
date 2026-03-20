<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

/**
 * SystemSettingsController
 * 
 * Controlador responsable de la configuración del sistema (White-labeling).
 * Implementa el "Motor de Atmósfera Dinámica" de Spincelaestream, permitiendo
 * personalizar fondos de pantalla globales, temas de usuario (Claro/Oscuro)
 * y logos de reportes.
 */
class SystemSettingsController extends Controller
{
    /**
     * Muestra la interfaz de configuración del sistema (Interfaz).
     */
    public function index()
    {
        return Inertia::render('Admin/Settings/Interface', [
            'settings' => SystemSetting::all(),
        ]);
    }

    /**
     * Muestra la configuración de reportes y notificaciones.
     */
    public function showReportSettings()
    {
        return Inertia::render('Admin/Settings/Reports', [
            'settings' => SystemSetting::whereIn('key', ['report_configuration', 'whatsapp_configuration'])->get(),
        ]);
    }

    /**
     * Actualiza la configuración global de branding (Logo, Colores, Favicon).
     */
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Encuentra el setting existente o prepara la creación si es una llave de branding válida.
            $setting = SystemSetting::where('key', $key)->first();
            
            if ($setting || in_array($key, ['app_name', 'footer_title', 'footer_subtitle', 'footer_brand', 'footer_year', 'color_primary_light', 'color_primary_dark', 'operation_mode', 'favicon'])) {
                
                if ($request->hasFile($key)) {
                    // Lógica de carga de archivos (Logos, Favicons).
                    if ($setting && $setting->value && !in_array($setting->value, ['logo.png', 'logo_small.png', 'logo_report.png', 'favicon.ico'])) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    
                    $path = $request->file($key)->store('branding', 'public');
                    SystemSetting::updateOrCreate(['key' => $key], ['value' => $path, 'type' => 'image']);
                } else {
                    // Actualización de cadenas de texto y colores.
                    if ($value !== null) {
                        SystemSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'text']);
                    }
                }
            }
        }

        return back()->with('success', 'Configuración actualizada.');
    }

    /**
     * Actualiza las preferencias individuales de tema de un usuario.
     * 
     * Implementa la persistencia de fondos dinámicos y modos de color
     * (Claro, Oscuro, Sistema) de Spincelaestream.
     */
    public function updateUserTheme(Request $request)
    {
        $validated = $request->validate([
            'background_url' => 'nullable|string',
            'theme_mode' => 'nullable|string|in:light,dark,system',
        ]);

        $user = $request->user();
        $settings = $user->theme_settings ?? [];
        
        if (isset($validated['background_url'])) $settings['background_url'] = $validated['background_url'];
        if (isset($validated['theme_mode'])) $settings['theme_mode'] = $validated['theme_mode'];

        $user->update(['theme_settings' => $settings]);

        return back()->with('success', 'Preferencia de tema guardada.');
    }

    /**
     * Gestiona el catálogo maestro de fondos globales para el sistema.
     */
    public function uploadBackground(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // Máximo 5MB
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('backgrounds', 'public');
            
            // Obtener catálogo existente.
            $setting = SystemSetting::firstOrCreate(
                ['key' => 'background_catalog'],
                ['value' => json_encode([]), 'type' => 'json']
            );
            
            $catalog = json_decode($setting->value, true) ?: [];
            $catalog[] = asset('storage/' . $path);
            
            $setting->update(['value' => json_encode($catalog)]);
        }

        return back()->with('success', 'Imagen añadida al catálogo.');
    }
}
