<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Area;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportExportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Utilities/DataManagement', [
            'stats' => [
                'users' => User::count(),
                'areas' => Area::count(),
                'providers' => Provider::count(),
            ]
        ]);
    }

    public function import(Request $request)
    {
        // Increase execution time for large imports
        ini_set('max_execution_time', 300); // 5 minutes

        $request->validate([
            'type' => 'required|in:users,areas,providers',
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $file = $request->file('file');
        $type = $request->type;

        // Native CSV parsing
        $path = $file->getRealPath();
        $rawData = array_map('str_getcsv', file($path));
        if (empty($rawData)) return back()->with('error', 'El archivo está vacío.');

        $header = array_shift($rawData);
        // Clean header: lowercase, trim and remove accents/special chars if possible
        $header = array_map(function($h) {
            return Str::slug(trim($h), '_');
        }, $header);

        $count = 0;
        $skipped = 0;

        // Performance caches
        $areasCache = [];
        $existingUsersByEmpNum = User::whereNotNull('employee_number')->pluck('id', 'employee_number')->toArray();
        $existingUsersByName = User::get()->mapWithKeys(function ($user) {
            $fullName = strtolower(trim($user->first_name . ' ' . $user->last_name . ' ' . $user->second_last_name));
            return [$fullName => $user->id];
        })->toArray();
        
        // Compute default password hash ONCE for the whole batch
        $defaultPasswordHash = Hash::make('password123');

        DB::beginTransaction();
        try {
            foreach ($rawData as $index => $row) {
                // Skip empty rows or rows that don't match header count
                if (empty($row) || count($row) !== count($header)) {
                    if (count(array_filter($row)) === 0) continue; // It's just an empty line
                    if (count($row) < count($header)) {
                        $row = array_pad($row, count($header), '');
                    } else {
                        $row = array_slice($row, 0, count($header));
                    }
                }

                $row = array_combine($header, $row);
                
                if ($type === 'areas') {
                    if (empty($row['nombre'])) continue;
                    $areaName = mb_strtoupper(trim($row['nombre']), 'UTF-8');
                    if (!isset($areasCache[$areaName])) {
                        $area = Area::firstOrCreate(['name' => $areaName]);
                        $areasCache[$areaName] = $area->id;
                        $count++;
                    }
                } 
                elseif ($type === 'providers') {
                    if (empty($row['nombre'])) continue;
                    Provider::updateOrCreate(
                        ['name' => mb_strtoupper(trim($row['nombre']), 'UTF-8')],
                        [
                            'contact_person' => $row['contacto'] ?? null,
                            'contact_phone' => $row['telefono'] ?? null,
                            'contact_email' => $row['email'] ?? null,
                            'address' => $row['direccion'] ?? null,
                        ]
                    );
                    $count++;
                }
                elseif ($type === 'users') {
                    $nombre = trim($row['nombre'] ?? '');
                    $paterno = trim($row['apellido_paterno'] ?? '');
                    $materno = trim($row['apellido_materno'] ?? '');
                    $noEmpleado = trim($row['no_empleado'] ?? '');

                    if (empty($nombre)) continue; 

                    // --- DUPLICATE DETECTION ---
                    $fullNameKey = strtolower(trim($nombre . ' ' . $paterno . ' ' . $materno));
                    
                    // If employee number exists in DB, or exact full name exists in DB, skip
                    if (!empty($noEmpleado) && isset($existingUsersByEmpNum[$noEmpleado])) {
                        $skipped++;
                        continue;
                    }
                    if (isset($existingUsersByName[$fullNameKey])) {
                        $skipped++;
                        continue;
                    }

                    // 1. Generate Username if missing
                    $username = !empty($row['usuario'] ?? '') 
                        ? trim($row['usuario']) 
                        : Str::slug(substr($nombre, 0, 1) . $paterno . ($count + 1)); // Added counter to avoid username collision
                    
                    // 2. Generate Email if missing
                    $email = !empty($row['email'] ?? '') 
                        ? trim($row['email']) 
                        : Str::slug($nombre . '.' . $paterno) . ($count + 1) . '@comedor.local';

                    // 3. Generate Employee Number if missing
                    $noEmpleado = !empty($noEmpleado) 
                        ? $noEmpleado 
                        : (User::max('employee_number') ?? 1000) + $count + 1;

                    // 4. Handle Area with local cache
                    $areaName = mb_strtoupper(trim($row['area'] ?? ''), 'UTF-8');
                    $areaId = null;
                    if (!empty($areaName)) {
                        if (!isset($areasCache[$areaName])) {
                            $area = Area::firstOrCreate(['name' => $areaName]);
                            $areasCache[$areaName] = $area->id;
                        }
                        $areaId = $areasCache[$areaName];
                    }

                    // 5. Use pre-computed hash if no specific password provided
                    $password = !empty($row['password']) ? Hash::make($row['password']) : $defaultPasswordHash;

                    $user = User::create([
                        'username' => $username,
                        'first_name' => $nombre,
                        'last_name' => $paterno,
                        'second_last_name' => $materno,
                        'email' => $email,
                        'employee_number' => $noEmpleado,
                        'password' => $password,
                        'role' => (!empty($row['rol'])) ? $row['rol'] : 'diner',
                        'area_id' => $areaId,
                    ]);

                    // Add to cache to prevent duplicates within the same file
                    $existingUsersByEmpNum[$noEmpleado] = $user->id;
                    $existingUsersByName[$fullNameKey] = $user->id;
                    $count++;
                }
            }
            DB::commit();
            
            $msg = "Se importaron $count registros correctamente.";
            if ($skipped > 0) {
                $msg .= " Se omitieron $skipped registros porque parecían ser duplicados (mismo número de empleado o nombre completo).";
            }
            return back()->with('success', $msg);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Error al importar: " . $e->getMessage());
        }
    }

    public function truncate(Request $request)
    {
        $request->validate(['type' => 'required|in:users,areas,providers,sessions,all']);
        $type = $request->type;

        if ($type === 'all' || $type === 'users') User::where('id', '!=', auth()->id())->delete();
        if ($type === 'all' || $type === 'areas') Area::whereDoesntHave('users')->delete();
        if ($type === 'all' || $type === 'providers') Provider::delete();
        
        if ($type === 'all' || $type === 'sessions') {
            // This clears all history: Orders, Authorizations, and Session status
            DB::statement('DELETE FROM orders');
            DB::statement('DELETE FROM session_authorizations');
            DB::statement('DELETE FROM provider_daily_statuses');
            DB::statement('DELETE FROM session_deletion_logs');
        }

        return back()->with('success', 'Base de datos limpiada correctamente (Respetando integridad).');
    }
}
