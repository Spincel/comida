<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Load areas with relationships but WITHOUT triggering recursive appends for everything
        $areas = Area::with(['parent', 'manager'])->withCount('users')->get();
        
        // Only calculate full_path and total_branch_users for what's needed
        $areas->map(function($area) {
            $area->append('full_path');
            if (!$area->parent_id) {
                $area->append('total_branch_users');
            }
            return $area;
        });

        $areas = $areas->sortBy('full_path')->values();
        
        // Potential managers (limited list)
        $potentialManagers = User::whereIn('role', ['area_manager', 'admin'])->take(100)->get();

        return Inertia::render('Admin/Areas/Index', [
            'areas' => $areas,
            'allAreas' => $areas, // Reusing the same collection
            'potentialManagers' => $potentialManagers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:areas,id',
        ]);

        // Capitalize area name for consistency
        $validated['name'] = mb_strtoupper($validated['name'], 'UTF-8');

        Area::create($validated);

        return back()->with('success', 'Área creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name,'.$area->id,
            'manager_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:areas,id',
        ]);

        $validated['name'] = mb_strtoupper($validated['name'], 'UTF-8');

        $area->update($validated);

        return back()->with('success', 'Área actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        if ($area->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el área porque tiene usuarios asignados.');
        }

        $area->delete();

        return back()->with('success', 'Área eliminada exitosamente.');
    }
}