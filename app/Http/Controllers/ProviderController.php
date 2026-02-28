<?php

namespace App\Http\Controllers;

use App\Models\Provider; // Import Provider model
use Illuminate\Http\Request;
use Inertia\Inertia; // Import Inertia

class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Providers/Index', [
            'providers' => Provider::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Providers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'delivery_time_window' => 'nullable|string|max:255',
        ]);

        Provider::create($validated);

        return redirect()->route('providers.index')->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Provider $provider) // Using route model binding
    {
        return Inertia::render('Admin/Providers/Edit', [
            'provider' => $provider,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Provider $provider) // Using route model binding
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'delivery_time_window' => 'nullable|string|max:255',
        ]);

        $provider->update($validated);

        return redirect()->route('providers.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Provider $provider) // Using route model binding
    {
        $provider->delete();

        return redirect()->route('providers.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}