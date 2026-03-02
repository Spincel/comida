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

    public function update(Request $request)
    {
        // Handle both single updates (auto-save) and potential batch updates
        $data = $request->all();

        foreach ($data as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            
            if ($setting) {
                if ($setting->type === 'image' && $request->hasFile($key)) {
                    // Delete old file if exists
                    if ($setting->value && !in_array($setting->value, ['logo.png', 'logo_small.png', 'logo_report.png'])) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    
                    $path = $request->file($key)->store('branding', 'public');
                    $setting->update(['value' => $path]);
                } else {
                    // String/Color updates
                    $setting->update(['value' => $value]);
                }
            }
        }

        return back()->with('success', 'Configuración actualizada.');
    }
}
