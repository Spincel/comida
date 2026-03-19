<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SystemSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Interface', [
            'settings' => SystemSetting::all(),
        ]);
    }

    public function showReportSettings()
    {
        return Inertia::render('Admin/Settings/Reports', [
            'settings' => SystemSetting::whereIn('key', ['report_configuration', 'whatsapp_configuration'])->get(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $key => $value) {
            // Find existing or prepare to create if it's a valid branding key
            $setting = SystemSetting::where('key', $key)->first();
            
            if ($setting || in_array($key, ['app_name', 'footer_title', 'footer_subtitle', 'footer_brand', 'footer_year', 'color_primary_light', 'color_primary_dark', 'operation_mode', 'favicon'])) {
                
                if ($request->hasFile($key)) {
                    // File upload logic
                    if ($setting && $setting->value && !in_array($setting->value, ['logo.png', 'logo_small.png', 'logo_report.png', 'favicon.ico'])) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    
                    $path = $request->file($key)->store('branding', 'public');
                    SystemSetting::updateOrCreate(['key' => $key], ['value' => $path, 'type' => 'image']);
                } else {
                    // String/Color updates
                    if ($value !== null) {
                        SystemSetting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'text']);
                    }
                }
            }
        }

        return back()->with('success', 'Configuración actualizada.');
    }

    /**
     * Update the authenticated user's individual theme preferences.
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
     * Handle background image uploads for the global catalog.
     */
    public function uploadBackground(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // Max 5MB
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('backgrounds', 'public');
            
            // Get existing catalog
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
