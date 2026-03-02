<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('area');

        // Filter by Area
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        // Filter by Role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Search in multiple fields
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('second_last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%")
                  ->orWhereHas('area', function($areaQuery) use ($search) {
                      $areaQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $allAreas = Area::all()->each->append('full_path')->sortBy('full_path')->values();

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->latest()->paginate(15)->withQueryString(),
            'areas' => $allAreas,
            'filters' => $request->only(['search', 'area_id', 'role']),
        ]);
    }

    private function generateUsername($firstName, $lastName)
    {
        $base = Str::slug(substr($firstName, 0, 1) . $lastName, '');
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255|unique:users',
            'email' => 'nullable|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:admin,acquisitions_manager,area_manager,diner',
            'area_id' => 'nullable|exists:areas,id',
            'avatar' => 'nullable|image|max:2048', // 2MB max
            'username' => 'nullable|string|max:255|unique:users',
        ]);

        $firstName = trim($request->first_name);
        $lastName = trim($request->last_name);
        
        $username = $request->username ?: $this->generateUsername($firstName, $lastName);
        
        // AUTO-GENERATE Email if missing
        $email = $request->email ?: Str::slug($firstName . '.' . $lastName) . '.' . rand(10, 99) . '@comedor.local';
        
        // AUTO-GENERATE Employee Number if missing
        $employeeNumber = $request->employee_number ?: (User::max('employee_number') ?? 1000) + 1;

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'second_last_name' => $request->second_last_name,
            'employee_number' => $employeeNumber,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'area_id' => $request->area_id,
            'avatar' => $avatarPath,
        ]);

        return back()->with('success', 'Usuario creado correctamente. Usuario de acceso: ' . $username);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255|unique:users,employee_number,'.$user->id,
            'email' => 'nullable|string|lowercase|email|max:255|unique:users,email,'.$user->id,
            'username' => 'nullable|string|max:255|unique:users,username,'.$user->id,
            'role' => 'required|string|in:admin,acquisitions_manager,area_manager,diner',
            'area_id' => 'nullable|exists:areas,id',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only([
            'first_name', 
            'last_name', 
            'second_last_name', 
            'employee_number', 
            'email', 
            'username', 
            'role', 
            'area_id'
        ]);
        
        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate([
                'password' => ['confirmed', Rules\Password::defaults()],
            ]);
            $data['password'] = Hash::make($request->password);
        }

        // Handle Avatar
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }
}
