<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Roles', [
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all()->groupBy('group'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'required|array',
        ]);

        $role->permissions()->sync($request->permissions);

        return back()->with('success', 'Permisos de rol actualizados correctamente.');
    }
}
