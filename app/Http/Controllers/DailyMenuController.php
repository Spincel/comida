<?php

namespace App\Http\Controllers;

use App\Models\DailyMenu;
use App\Models\Provider;
use App\Models\ProviderDailyStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use GuzzleHttp\Client; // For Gemini API HTTP calls
use Illuminate\Support\Facades\Log; // For logging errors
use Illuminate\Support\Facades\File; // For file operations
use Illuminate\Http\JsonResponse; // For JSON response

class DailyMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $selectedProviderId = $request->input('provider_id');

        $query = DailyMenu::with('provider');

        if ($selectedProviderId) {
            $query->where('provider_id', $selectedProviderId);
        } else {
            // Only filter by date if NO provider is selected (general view)
            $query->where('available_on', $selectedDate);
        }

        $menus = $query->get()->map(function($menu) {
            // Calculate popularity based on historical orders of the same dish name for this provider
            $historicalCount = \App\Models\Order::whereHas('dailyMenu', function($q) use ($menu) {
                $q->where('provider_id', $menu->provider_id)
                  ->where('name', $menu->name);
            })->count();

            $menu->historical_orders_count = $historicalCount;
            
            if ($historicalCount === 0) {
                $menu->popularity_label = 'Nunca Solicitado';
                $menu->popularity_color = 'bg-gray-100 text-gray-500 border-gray-200';
            } elseif ($historicalCount >= 50) {
                $menu->popularity_label = 'Platillo Estrella';
                $menu->popularity_color = 'bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400';
            } elseif ($historicalCount >= 20) {
                $menu->popularity_label = 'Muy Solicitado';
                $menu->popularity_color = 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400';
            } else {
                $menu->popularity_label = 'Regularmente';
                $menu->popularity_color = 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400';
            }

            return $menu;
        });

        $providers = Provider::all();

        $providerDailyStatus = null;
        if ($selectedProviderId) {
            $providerDailyStatus = ProviderDailyStatus::where('provider_id', $selectedProviderId)
                                                      ->where('date', $selectedDate)
                                                      ->first();
        }

        return Inertia::render('Admin/DailyMenus/Index', [
            'menus' => $menus,
            'providers' => $providers,
            'selectedDate' => $selectedDate,
            'selectedProviderId' => $selectedProviderId ? (int)$selectedProviderId : null,
            'providerDailyStatus' => $providerDailyStatus,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/DailyMenus/Create', [
            'providers' => Provider::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'available_on' => 'required|date',
            'provider_id' => 'required|integer|exists:providers,id', // Add provider_id validation
        ]);

        DailyMenu::create($validated);

        return redirect()->route('daily-menus.index')->with('success', 'Menú creado exitosamente.');
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
    public function edit(DailyMenu $dailyMenu) // Using route model binding
    {
        return Inertia::render('Admin/DailyMenus/Edit', [
            'dailyMenu' => $dailyMenu,
            'providers' => Provider::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyMenu $dailyMenu) // Using route model binding
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'available_on' => 'required|date',
            'provider_id' => 'required|integer|exists:providers,id',
            // 'status' => 'required|string|in:draft,published,closed', // Validate status
        ]);

        $dailyMenu->update($validated);

        return redirect()->route('daily-menus.index')->with('success', 'Menú actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyMenu $dailyMenu) // Using route model binding
    {
        $dailyMenu->delete();

        return redirect()->route('daily-menus.index')->with('success', 'Menú eliminado exitosamente.');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, DailyMenu $dailyMenu)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,published,closed',
        ]);

        $dailyMenu->update($validated);

        return redirect()->route('daily-menus.index')->with('success', 'Estado del menú actualizado exitosamente.');
    }

    /**
     * Update the status of a provider for a specific date.
     */
    public function updateProviderDailyStatus(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|integer|exists:providers,id',
            'date' => 'required|date',
            'status' => 'required|string|in:open,closed', // New status for provider daily status
        ]);

        ProviderDailyStatus::updateOrCreate(
            ['provider_id' => $validated['provider_id'], 'date' => $validated['date']],
            ['status' => $validated['status']]
        );

        return redirect()->route('daily-menus.index', [
            'date' => $validated['date'],
            'provider_id' => $validated['provider_id'],
        ])->with('success', 'Estado de pedidos del proveedor actualizado exitosamente.');
    }

    /**
     * Publish all draft menu items for a provider on a specific date.
     */
    public function publishAll(Request $request)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'date' => 'required|date',
        ]);

        $updatedCount = DailyMenu::where('provider_id', $validated['provider_id'])
            ->where('available_on', $validated['date'])
            ->where('status', 'draft')
            ->update(['status' => 'published']);

        return back()->with('success', "Se han publicado $updatedCount platillos correctamente.");
    }

    /**
     * Get existing menu items for a provider on a specific date.
     */
    public function getExistingItems(Request $request, Provider $provider): JsonResponse
    {
        // Ignore date filter to check against PERMANENT catalog duplicates
        $items = DailyMenu::where('provider_id', $provider->id)
                          ->get(['id', 'name', 'description', 'status']);
        
        return response()->json(['items' => $items]);
    }

    /**
     * Scan an uploaded menu image/PDF using AI.
     */
    public function scanMenu(Request $request, Provider $provider): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,pdf|max:10240', // Max 10MB
        ]);

        $uploadedFile = $request->file('file');
        $mimeType = $uploadedFile->getMimeType();
        $fileContentBase64 = base64_encode(File::get($uploadedFile->getRealPath()));

        // Increase execution time for AI processing
        set_time_limit(120);

        $geminiApiKey = env('GEMINI_API_KEY');
        if (!$geminiApiKey) {
            Log::error('GEMINI_API_KEY not set in .env');
            return response()->json(['error' => 'La clave API de Gemini no está configurada.'], 500);
        }

        $client = new Client([
            'timeout' => 60.0, // 60 seconds timeout
            'connect_timeout' => 10.0
        ]);
        // Use gemini-flash-latest as it is available and has active quota
        $geminiEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$geminiApiKey}";

        $prompt = "Analiza esta imagen o PDF de un menú de comida. Extrae todos los platillos disponibles y sus descripciones.
        
        INSTRUCCIÓN IMPORTANTE PARA CATEGORÍAS:
        Si el menú tiene secciones o categorías (ejemplo: 'CEVICHES' y debajo 'Pescado', 'Camarón'), debes combinar el nombre de la categoría con el platillo para que sea descriptivo. 
        Ejemplo: En lugar de solo 'Pescado', devuelve 'Ceviche de Pescado'. 
        Lo mismo para 'Tacos', 'Tortas', etc. El objetivo es que cada nombre de platillo sea auto-explicativo por sí solo.

        Devuelve la información estrictamente en formato JSON como un arreglo de objetos.
        Ejemplo de formato JSON esperado:
        [
            {
                \"name\": \"Ceviche de Pescado\",
                \"description\": \"Con cebolla, cilantro y limón\"
            },
            {
                \"name\": \"Taco de Asada\",
                \"description\": \"Con tortilla de maíz y salsa roja\"
            }
        ]
        Si no hay una descripción clara, deja el campo 'description' vacío. No incluyas explicaciones adicionales, solo el JSON.";

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

            // Extract the text part from Gemini's response
            $geminiText = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$geminiText) {
                Log::error("Gemini empty response: " . json_encode($geminiResponse));
                return response()->json(['error' => 'No se pudo obtener una respuesta válida de la IA.'], 500);
            }

            // Gemini sometimes wraps JSON in markdown code blocks even when asked for application/json
            $geminiText = trim($geminiText);
            if (str_starts_with($geminiText, '```json')) {
                $geminiText = preg_replace('/^```json\s*|```\s*$/', '', $geminiText);
            } elseif (str_starts_with($geminiText, '```')) {
                $geminiText = preg_replace('/^```\s*|```\s*$/', '', $geminiText);
            }
            
            $parsedMenu = json_decode($geminiText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Gemini JSON parse error: " . json_last_error_msg() . " Raw response: " . $geminiText);
                return response()->json([
                    'error' => 'Error al procesar el formato del menú extraído.',
                    'details' => 'La IA devolvió un formato no válido. Intenta con una imagen más clara.',
                    'raw_response' => $geminiText
                ], 500);
            }

            return response()->json(['menu_items' => $parsedMenu]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            Log::error("Gemini API Client Error: " . $e->getMessage() . " Response: " . $responseBody);
            return response()->json(['error' => 'Error de conexión con el servicio de IA.', 'details' => $responseBody], 500);
        } catch (\Exception $e) {
            Log::error("Gemini API General Error: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado al escanear el menú.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Store multiple menu items at once.
     */
    public function batchStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'provider_id' => 'required|integer|exists:providers,id',
            'available_on' => 'required|date',
        ]);

        $createdItems = [];
        foreach ($validated['items'] as $item) {
            $createdItems[] = DailyMenu::create([
                'name' => $item['name'],
                'description' => $item['description'],
                'provider_id' => $validated['provider_id'],
                'available_on' => $validated['available_on'],
                'status' => 'published', // Set to published automatically
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($createdItems) . ' platillos guardados exitosamente como borradores.',
            'items' => $createdItems
        ]);
    }
}
