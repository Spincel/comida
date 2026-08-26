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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('users.view')) {
            abort(403, 'No tienes permiso para ver la lista de usuarios.');
        }

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
        if (!$request->user()->hasPermission('users.manage')) {
            abort(403, 'No tienes permiso para crear usuarios.');
        }

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
        $employeeNumber = $request->employee_number;
        if (!$employeeNumber) {
            $maxEmployeeNumber = User::selectRaw('MAX(CAST(employee_number AS INTEGER)) as max_val')
                ->value('max_val');
            $employeeNumber = ($maxEmployeeNumber ?: 1000) + 1;
        }

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
        if (!auth()->user()->hasPermission('users.manage')) {
            abort(403, 'No tienes permiso para eliminar usuarios.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return back()->with('success', 'Usuario desactivado correctamente.');
    }

    /**
     * Display the manager's team members.
     */
    public function indexTeam(Request $request)
    {
        $user = $request->user();
        if (!$user->area_id) abort(403, 'No tienes un área asignada.');

        $team = User::withTrashed()
            ->where('area_id', $user->area_id)
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

    /**
     * Scan image or document using Gemini AI to extract users and their areas.
     */
    public function scanDocument(Request $request)
    {
        if (!$request->user()->hasPermission('users.manage')) {
            return response()->json(['error' => 'No tienes permiso para gestionar usuarios.'], 403);
        }

        $request->validate([
            'document' => 'required|file|max:15360', // 15MB max
        ], [
            'document.required' => 'Debes adjuntar una imagen o documento.',
            'document.max' => 'El archivo no debe exceder los 15MB.',
        ]);

        $file = $request->file('document');
        $mimeType = $file->getMimeType();
        $fileContentBase64 = base64_encode(file_get_contents($file->getRealPath()));

        $geminiApiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if (!$geminiApiKey) {
            return response()->json([
                'error' => 'Falta configurar la clave API de Gemini (GEMINI_API_KEY) en el archivo .env.'
            ], 500);
        }

        $client = new Client([
            'timeout' => 90.0,
            'connect_timeout' => 15.0
        ]);

        $candidateModels = [
            'gemini-3.6-flash',
            'gemini-3.7-flash',
            'gemini-3.5-flash',
            'gemini-flash-latest'
        ];

        $prompt = "Analiza esta imagen o documento que puede ser un organigrama estructural, plantilla laboral, lista de personal o nómina de una institución / empresa.
        
        INSTRUCCIONES DE EXTRACCIÓN:
        1. Si el documento es un ORGANIGRAMA ESTRUCTURAL (contiene recuadros con nombres de áreas, direcciones, unidades, departamentos, coordinaciones o secretarías):
           - Extrae ABSOLUTAMENTE TODAS las áreas, departamentos, direcciones, unidades, coordinaciones y secretarías identificadas en el organigrama.
           - Para cada recuadro/área encontrada:
             * 'area_name': Nombre completo y exacto del área, departamento o unidad institucional (ej: 'Dirección de Proceso Legislativo', 'Unidad de Tecnologías de la Información y Comunicaciones', 'Departamento de Adquisiciones', etc.).
             * 'position': Puesto o cargo estructural (ej: 'Titular de Área', 'Director(a)', 'Jefe(a) de Departamento', 'Encargado(a)').
             * Si el recuadro contiene el nombre de una persona concreta, extráelo. Si no tiene nombre de persona física, asigna:
               'first_name': 'Titular',
               'last_name': Palabra clave o nombre corto del área (ej: 'Proceso Legislativo', 'UTICs', 'Adquisiciones', 'Tesorería', etc.),
               'second_last_name': '',
               'full_name': 'Titular - ' . area_name,
               'role': 'area_manager' (Gerente de Área),
               'email': slug(area_name) . '@congresonay.gob.mx'.

        2. Si el documento es una LISTA / NÓMINA DE PERSONAS con sus áreas asignadas:
           - Extrae a cada una de las personas detectadas en el documento.
           - Desglosa:
             * 'first_name': Nombre o nombres de pila
             * 'last_name': Primer apellido o apellido paterno
             * 'second_last_name': Segundo apellido o materno (si existe)
             * 'full_name': Nombre completo normalizado
             * 'area_name': Área o departamento al que pertenece
             * 'employee_number': Número de empleado, clave o matrícula (si está disponible)
             * 'position': Puesto o cargo
             * 'role': 'area_manager' si es titular/director/gerente, o 'diner' para el resto del personal
             * 'email': Correo si viene en el documento o autogenerado

        Devuelve la información estrictamente en formato JSON como un arreglo de objetos:
        [
            {
                \"first_name\": \"Titular\",
                \"last_name\": \"Adquisiciones\",
                \"second_last_name\": \"\",
                \"full_name\": \"Titular - Dirección de Adquisiciones\",
                \"area_name\": \"Dirección de Adquisiciones, Servicios Generales, Control de Bienes y Almacén\",
                \"position\": \"Titular de Dirección\",
                \"role\": \"area_manager\",
                \"employee_number\": null,
                \"email\": \"adquisiciones@congresonay.gob.mx\"
            }
        ]
        No incluyas explicaciones en texto ni bloques markdown adicionales, únicamente el arreglo JSON puro.";

        $lastError = null;

        foreach ($candidateModels as $model) {
            $geminiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiApiKey}";

            try {
                $response = $client->post($geminiEndpoint, [
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inlineData' => [
                                            'mimeType' => $mimeType,
                                            'data' => $fileContentBase64
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                        ]
                    ]
                ]);

                $geminiResponse = json_decode($response->getBody()->getContents(), true);
                $geminiText = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$geminiText) {
                    Log::warning("Gemini empty response with model {$model}: " . json_encode($geminiResponse));
                    continue;
                }

                $geminiText = trim($geminiText);
                if (str_starts_with($geminiText, '```json')) {
                    $geminiText = preg_replace('/^```json\s*|```\s*$/', '', $geminiText);
                } elseif (str_starts_with($geminiText, '```')) {
                    $geminiText = preg_replace('/^```\s*|```\s*$/', '', $geminiText);
                }

                $parsedUsers = json_decode($geminiText, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsedUsers)) {
                    Log::error("Gemini user JSON parse error: " . json_last_error_msg() . " Raw response: " . $geminiText);
                    continue;
                }

                // Get existing areas for matching
                $existingAreas = Area::all(['id', 'name']);

                // Enhance parsed users with auto-matched area_id if exists
                $normalizedUsers = [];
                foreach ($parsedUsers as $u) {
                    $areaName = trim($u['area_name'] ?? 'Sin Área');
                    $matchedArea = $existingAreas->first(function($a) use ($areaName) {
                        return strcasecmp($a->name, $areaName) === 0 || 
                               stripos($a->name, $areaName) !== false || 
                               stripos($areaName, $a->name) !== false;
                    });

                    $firstName = trim($u['first_name'] ?? '');
                    $lastName = trim($u['last_name'] ?? '');
                    $secondLastName = trim($u['second_last_name'] ?? '');
                    $fullName = trim($u['full_name'] ?? ($firstName . ' ' . $lastName . ' ' . $secondLastName));

                    $normalizedUsers[] = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'second_last_name' => $secondLastName,
                        'full_name' => $fullName,
                        'area_name' => $areaName,
                        'area_id' => $matchedArea ? $matchedArea->id : null,
                        'employee_number' => $u['employee_number'] ?? null,
                        'position' => $u['position'] ?? '',
                        'role' => in_array($u['role'] ?? '', ['admin', 'acquisitions_manager', 'area_manager', 'diner']) ? $u['role'] : 'diner',
                        'email' => !empty($u['email']) ? $u['email'] : Str::slug($firstName . '.' . $lastName) . rand(10, 99) . '@congresonay.gob.mx',
                        'selected' => true,
                    ];
                }

                return response()->json([
                    'users' => $normalizedUsers,
                    'total_detected' => count($normalizedUsers)
                ]);

            } catch (\Exception $e) {
                Log::error("Error scanning users with Gemini model {$model}: " . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        return response()->json([
            'error' => 'No se pudo procesar el documento con Inteligencia Artificial. Verifica que el archivo sea legible.',
            'details' => $lastError
        ], 500);
    }

    /**
     * Scan officials and areas from a public URL using Gemini AI.
     */
    public function scanUrl(Request $request)
    {
        if (!$request->user()->hasPermission('users.manage')) {
            return response()->json(['error' => 'No tienes permiso para gestionar usuarios.'], 403);
        }

        $request->validate([
            'url' => 'required|url',
        ], [
            'url.required' => 'Debes ingresar una URL válida.',
            'url.url' => 'El formato del enlace web no es válido.',
        ]);

        $url = trim($request->url);
        $client = new Client([
            'timeout' => 45.0,
            'connect_timeout' => 15.0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);

        try {
            $webResponse = $client->get($url);
            $html = $webResponse->getBody()->getContents();
            $cleanHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
            $cleanHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleanHtml);
            $text = strip_tags($cleanHtml);
            $text = preg_replace('/\s+/', ' ', $text);
            $textSnippet = mb_substr($text, 0, 30000);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo acceder al enlace web proporcionado. Verifica que la página sea pública y accesible.',
                'details' => $e->getMessage()
            ], 422);
        }

        $geminiApiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if (!$geminiApiKey) {
            return response()->json([
                'error' => 'Falta configurar la clave API de Gemini (GEMINI_API_KEY) en el archivo .env.'
            ], 500);
        }

        $candidateModels = [
            'gemini-3.5-flash',
            'gemini-3.6-flash',
            'gemini-3.7-flash',
            'gemini-flash-latest'
        ];

        $prompt = "Analiza el siguiente texto extraído de la página web del directorio oficial de funcionarios y áreas ({$url}):

CONTENIDO DEL SITIO WEB:
\"{$textSnippet}\"

INSTRUCCIONES DE EXTRACCIÓN:
1. Extrae a cada uno de los funcionarios, titulares y personal listados en el directorio.
2. Limpia grados y títulos académicos ('Lic.', 'Ing.', 'Mtro.', 'Dip.', 'Dr.', 'L.C.', etc.) de los nombres.
3. Desglosa los nombres en:
   - 'first_name': Nombre(s) de pila
   - 'last_name': Primer apellido / Paterno
   - 'second_last_name': Segundo apellido / Materno (si existe)
   - 'full_name': Nombre completo limpio
   - 'area_name': Nombre del Área, Dirección, Unidad o Secretaría a la que pertenece o dirige
   - 'position': Cargo oficial completo
   - 'role': 'area_manager' (Gerente de Área) para titulares y directores, o 'diner' para personal de apoyo
   - 'email': Correo institucional exacto mencionado en la página
   - 'employee_number': (opcional)

Devuelve estrictamente un arreglo JSON puro de objetos:
[
  {
    \"first_name\": \"Raúl Rafael\",
    \"last_name\": \"Flores\",
    \"second_last_name\": \"Pérez\",
    \"full_name\": \"Raúl Rafael Flores Pérez\",
    \"area_name\": \"Unidad de Tecnologías de la Información y Comunicaciones\",
    \"position\": \"Encargado de la Unidad de Tecnologías de la Información y Comunicaciones\",
    \"role\": \"area_manager\",
    \"email\": \"utics@congresonayarit.gob.mx\"
  }
]
No incluyas explicaciones en texto ni comentarios markdown, solo el JSON.";

        $lastError = null;

        foreach ($candidateModels as $model) {
            $geminiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiApiKey}";

            try {
                $response = $client->post($geminiEndpoint, [
                    'json' => [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                        ]
                    ]
                ]);

                $geminiResponse = json_decode($response->getBody()->getContents(), true);
                $geminiText = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if (!$geminiText) {
                    Log::warning("Gemini empty response with model {$model}: " . json_encode($geminiResponse));
                    continue;
                }

                $geminiText = trim($geminiText);
                if (str_starts_with($geminiText, '```json')) {
                    $geminiText = preg_replace('/^```json\s*|```\s*$/', '', $geminiText);
                } elseif (str_starts_with($geminiText, '```')) {
                    $geminiText = preg_replace('/^```\s*|```\s*$/', '', $geminiText);
                }

                $parsedUsers = json_decode($geminiText, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsedUsers)) {
                    Log::error("Gemini user JSON parse error from URL: " . json_last_error_msg() . " Raw response: " . $geminiText);
                    continue;
                }

                // Get existing areas for matching
                $existingAreas = Area::all(['id', 'name']);

                // Enhance parsed users with auto-matched area_id if exists
                $normalizedUsers = [];
                foreach ($parsedUsers as $u) {
                    $areaName = trim($u['area_name'] ?? 'Sin Área');
                    $matchedArea = $existingAreas->first(function($a) use ($areaName) {
                        return strcasecmp($a->name, $areaName) === 0 || 
                               stripos($a->name, $areaName) !== false || 
                               stripos($areaName, $a->name) !== false;
                    });

                    $firstName = trim($u['first_name'] ?? '');
                    $lastName = trim($u['last_name'] ?? '');
                    $secondLastName = trim($u['second_last_name'] ?? '');
                    $fullName = trim($u['full_name'] ?? ($firstName . ' ' . $lastName . ' ' . $secondLastName));

                    $normalizedUsers[] = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'second_last_name' => $secondLastName,
                        'full_name' => $fullName,
                        'area_name' => $areaName,
                        'area_id' => $matchedArea ? $matchedArea->id : null,
                        'employee_number' => $u['employee_number'] ?? null,
                        'position' => $u['position'] ?? '',
                        'role' => in_array($u['role'] ?? '', ['admin', 'acquisitions_manager', 'area_manager', 'diner']) ? $u['role'] : 'area_manager',
                        'email' => !empty($u['email']) ? $u['email'] : Str::slug($firstName . '.' . $lastName) . rand(10, 99) . '@congresonay.gob.mx',
                        'selected' => true,
                    ];
                }

                return response()->json([
                    'users' => $normalizedUsers,
                    'total_detected' => count($normalizedUsers)
                ]);

            } catch (\Exception $e) {
                Log::error("Error scanning URL with Gemini model {$model}: " . $e->getMessage());
                $lastError = $e->getMessage();
            }
        }

        return response()->json([
            'error' => 'No se pudo procesar la URL con Inteligencia Artificial. Intenta nuevamente.',
            'details' => $lastError
        ], 500);
    }

    /**
     * Batch import users and automatically create areas if needed.
     */
    public function batchImport(Request $request)
    {
        if (!$request->user()->hasPermission('users.manage')) {
            abort(403, 'No tienes permiso para importar usuarios.');
        }

        $validated = $request->validate([
            'users' => 'required|array|min:1',
            'users.*.first_name' => 'required|string|max:255',
            'users.*.last_name' => 'required|string|max:255',
            'users.*.second_last_name' => 'nullable|string|max:255',
            'users.*.area_id' => 'nullable',
            'users.*.area_name' => 'nullable|string|max:255',
            'users.*.role' => 'required|string|in:admin,acquisitions_manager,area_manager,diner',
            'users.*.employee_number' => 'nullable|string|max:255',
            'users.*.email' => 'nullable|string|max:255',
            'default_password' => 'nullable|string|min:6',
        ]);

        $defaultPassword = $validated['default_password'] ?: 'password123';
        $hashedPassword = Hash::make($defaultPassword);

        $createdCount = 0;
        $updatedCount = 0;
        $createdAreasCount = 0;

        DB::transaction(function() use ($validated, $hashedPassword, &$createdCount, &$updatedCount, &$createdAreasCount) {
            foreach ($validated['users'] as $u) {
                // 1. Resolve or Create Area
                $areaId = $u['area_id'] ?? null;
                if (!$areaId && !empty($u['area_name'])) {
                    $areaName = trim($u['area_name']);
                    $existingArea = Area::where('name', $areaName)->first();
                    if ($existingArea) {
                        $areaId = $existingArea->id;
                    } else {
                        $newArea = Area::create(['name' => $areaName]);
                        $areaId = $newArea->id;
                        $createdAreasCount++;
                    }
                }

                $firstName = trim($u['first_name']);
                $lastName = trim($u['last_name']);
                $secondLastName = trim($u['second_last_name'] ?? '');
                $fullName = trim($firstName . ' ' . $lastName . ' ' . $secondLastName);
                $employeeNumber = !empty($u['employee_number']) ? trim($u['employee_number']) : null;
                
                // 2. Check if user already exists
                $existingUser = null;
                if ($employeeNumber) {
                    $existingUser = User::where('employee_number', $employeeNumber)->first();
                }
                if (!$existingUser && !empty($u['email'])) {
                    $existingUser = User::where('email', trim($u['email']))->first();
                }
                if (!$existingUser) {
                    $existingUser = User::where('first_name', $firstName)->where('last_name', $lastName)->first();
                }

                if ($existingUser) {
                    $existingUser->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'second_last_name' => $secondLastName,
                        'name' => $fullName,
                        'area_id' => $areaId ?: $existingUser->area_id,
                        'role' => $u['role'] ?: $existingUser->role,
                        'employee_number' => $employeeNumber ?: $existingUser->employee_number,
                    ]);
                    $updatedCount++;
                } else {
                    $username = $this->generateUsername($firstName, $lastName);
                    $email = !empty($u['email']) ? trim($u['email']) : Str::slug($firstName . '.' . $lastName) . rand(10, 99) . '@congresonay.gob.mx';
                    
                    // Ensure unique email
                    while (User::where('email', $email)->exists()) {
                        $email = Str::slug($firstName . '.' . $lastName) . rand(100, 999) . '@congresonay.gob.mx';
                    }

                    User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'second_last_name' => $secondLastName,
                        'name' => $fullName,
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashedPassword,
                        'role' => $u['role'] ?: 'diner',
                        'area_id' => $areaId,
                        'employee_number' => $employeeNumber,
                        'status' => 'active',
                    ]);
                    $createdCount++;
                }
            }
        });

        $msg = "Importación completada: Se crearon {$createdCount} usuarios nuevos";
        if ($updatedCount > 0) $msg .= ", se actualizaron {$updatedCount} existentes";
        if ($createdAreasCount > 0) $msg .= " y se crearon {$createdAreasCount} áreas nuevas";
        $msg .= ".";

        return back()->with('success', $msg);
    }
}
