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

    /**
     * Display the manager's team members.
     */
    public function indexTeam(Request $request)
    {
        $user = $request->user();
        if (!$user->area_id) abort(403, 'No tienes un área asignada.');

        $team = User::where('area_id', $user->area_id)
            ->where('id', '!=', $user->id) // Hide self
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Admin/Users/TeamManagement', [
            'team' => $team,
            'area' => $user->area
        ]);
    }

    /**
     * Store a new member in the manager's area.
     */
    public function storeTeam(Request $request)
    {
        $manager = $request->user();
        if (!$manager->area_id) abort(403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:5120', // Max 5MB
        ]);

        // Auto-generate credentials
        $baseUsername = strtolower(substr($validated['first_name'], 0, 1) . str_replace(' ', '', $validated['last_name']));
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter++;
        }

        $employeeNumber = 'TEMP' . rand(1000, 9999);
        while (User::where('employee_number', $employeeNumber)->exists()) {
            $employeeNumber = 'TEMP' . rand(1000, 9999);
        }

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'second_last_name' => $validated['second_last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name'] . ' ' . ($validated['second_last_name'] ?? '')),
            'username' => $username,
            'email' => $username . '@comedor.local',
            'employee_number' => $employeeNumber,
            'avatar' => $avatarPath,
            'password' => \Illuminate\Support\Facades\Hash::make($employeeNumber),
            'role' => 'diner',
            'area_id' => $manager->area_id,
            'status' => 'active',
        ]);

        return back()->with('success', 'Nuevo comensal añadido a la plantilla.');
    }

    /**
     * Update a team member's information.
     */
    public function updateTeam(Request $request, User $user)
    {
        $manager = $request->user();
        if ($user->area_id !== $manager->area_id) abort(403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'second_last_name' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:5120',
        ]);

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'second_last_name' => $validated['second_last_name'],
            'name' => trim($validated['first_name'] . ' ' . $validated['last_name'] . ' ' . ($validated['second_last_name'] ?? '')),
        ];

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Información actualizada.');
    }

    /**
     * Toggle status (enable/disable) for a team member.
     */
    public function toggleTeamStatus(Request $request, User $user)
    {
        $manager = $request->user();
        if ($user->area_id !== $manager->area_id) abort(403);

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active'
        ]);

        return back()->with('success', 'Estatus del comensal actualizado.');
    }
}
