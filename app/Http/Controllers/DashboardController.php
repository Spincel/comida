<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\ProviderDailyStatus;
use App\Models\Area;
use App\Models\Order;
use App\Models\DailyMenu;
use App\Models\User;
use App\Models\SessionAuthorization;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * DashboardController
 * 
 * Controlador principal del sistema SICOA (Spincelaestream).
 * Gestiona la lógica del tablero de control, el ciclo de vida de las sesiones de comida,
 * las autorizaciones por dependencia y la generación de reportes operativos.
 */
class DashboardController extends Controller
{
    /**
     * Muestra la vista principal del Dashboard.
     * 
     * Implementa la lógica de:
     * 1. Auto-cierre de sesiones: Cierra automáticamente sesiones de días previos.
     * 2. Filtrado por área: Los usuarios solo ven menús y sesiones autorizadas para su dependencia.
     * 3. Monitor de Adquisiciones: Vista global para administradores con conteo de platillos en tiempo real.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // AUTO-CLOSE: Lógica de limpieza automática.
        // Cualquier sesión que se haya quedado abierta de días anteriores se cierra
        // al momento de que cualquier usuario entra al Dashboard hoy.
        ProviderDailyStatus::where('status', 'open')
            ->where('date', '<', $today)
            ->update(['status' => 'closed']);

        $operationMode = SystemSetting::where('key', 'operation_mode')->first()?->value ?? 'complete';
        
        $props = [
            'userRole' => $user->role,
            'operationMode' => $operationMode
        ];

        // 1. Get ALL sessions for today (Base for filtering)
        $allSessionsToday = ProviderDailyStatus::where('date', $today)->with('provider')->get();
        $openStatusesToday = $allSessionsToday->where('status', 'open')->values();
        $props['closedTodaySessions'] = $allSessionsToday->where('status', 'closed')->values();
        $props['allSessionsToday'] = $allSessionsToday->values();

        // 2. Identify sessions relevant to the user's area
        $myAreaSessions = collect();
        if ($user->area_id) {
            $myAreaSessions = $openStatusesToday->filter(function($session) use ($user) {
                // Ensure we handle both array and JSON string, and force integer comparison
                $selectedAreaIds = $session->selected_area_ids;
                if (!is_array($selectedAreaIds)) {
                    $selectedAreaIds = json_decode($selectedAreaIds, true) ?: [];
                }
                $selectedAreaIds = array_map('intval', $selectedAreaIds);
                return in_array((int)$user->area_id, $selectedAreaIds);
            })->values();
        }

        // 3. Logic for ACQUISITIONS / ADMIN
        if ($user->role === 'acquisitions_manager' || $user->role === 'admin') {
            $allAreas = Area::all();
            $allAreas->each->append('full_path');
            
            $providers = Provider::all()->map(function ($provider) use ($today) {
                $provider->dailyStatuses = ProviderDailyStatus::where('provider_id', $provider->id)
                                                          ->where('date', $today)
                                                          ->get();
                $provider->dailyStatus = $provider->dailyStatuses->where('status', 'open')->first() 
                                        ?? $provider->dailyStatuses->sortByDesc('activated_at')->first();
                return $provider;
            });

            // Detailed tracking for ALL open sessions (Acquisitions View)
            $openSessionsDetailed = $openStatusesToday->map(function($session) use ($allAreas, $today) {
                $selectedAreaIds = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                $selectedAreaIds = array_map('intval', $selectedAreaIds ?: []);
                
                $sessionOrders = Order::where('meal_type', $session->meal_type)
                    ->whereDate('orders.created_at', $today)
                    ->where('status', '!=', 'cancelled')
                    ->whereHas('dailyMenu', function($q) use ($session) {
                        $q->where('provider_id', $session->provider->id);
                    })->with('user')->get();

                $session->areas_status = $allAreas->whereIn('id', $selectedAreaIds)->map(function($area) use ($sessionOrders) {
                    $areaOrders = $sessionOrders->where('user.area_id', $area->id);
                    $total = $areaOrders->count();
                    $submitted = $areaOrders->where('status', 'submitted_by_manager')->count();
                    $pending = $areaOrders->where('status', 'submitted_by_user')->count();

                    return [
                        'id' => $area->id,
                        'name' => $area->name,
                        'is_submitted' => $total > 0 && $pending === 0, // All are confirmed
                        'is_pending' => $pending > 0, // At least one is not confirmed yet
                        'order_count' => $total,
                        'submitted_count' => $submitted,
                    ];
                })->values();

                return $session;
            });

            $props['providers'] = $providers;
            $props['areas'] = $allAreas; 
            $props['openSessions'] = $openSessionsDetailed->values();
            $props['allOpenSessionsToday'] = $openStatusesToday; 

            // Active orders summary for acquisitions monitor (Real-time: User + Manager submitted)
            $activeOrders = Order::whereIn('status', ['submitted_by_user', 'submitted_by_manager'])
                ->whereDate('orders.created_at', $today)
                ->with(['user.area', 'dailyMenu'])
                ->get();

            $props['dishSummaryToday'] = $activeOrders->groupBy(fn($o) => $o->meal_type . '_' . $o->dailyMenu->provider->id)
                ->map(fn($mealOrders) => [
                    'meal_type' => $mealOrders->first()->meal_type,
                    'provider_id' => $mealOrders->first()->dailyMenu->provider->id,
                    'total' => $mealOrders->count(),
                    'dishes' => $mealOrders->groupBy('dailyMenu.name')->map(fn($orders, $name) => ['name' => $name, 'count' => $orders->count()])->values()->sortByDesc('count')->values(),
                ])->values();
        }

        // 4. Logic for AREA MANAGER / DINER
        if ($user->area_id) {
            $areaUsers = User::where('area_id', $user->area_id)->get();
            $areaUserIds = $areaUsers->pluck('id');
            $allMyAreaIds = [$user->area_id]; // For history queries later

            // Authorizations for sessions that include my area
            $allAreaAuthorizations = SessionAuthorization::whereIn('user_id', $areaUserIds)
                ->whereIn('provider_daily_status_id', $openStatusesToday->pluck('id'))
                ->get();

            $userAuthorizations = $allAreaAuthorizations->where('user_id', $user->id)
                ->pluck('provider_daily_status_id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            $teamOrders = Order::whereIn('user_id', $areaUserIds)
                                ->whereDate('orders.created_at', $today)
                                ->where('status', '!=', 'cancelled') // CRITICAL: Ignore cancelled orders
                                ->with(['user' => fn($q) => $q->withTrashed(), 'dailyMenu.provider'])
                                ->get();
            
            $props['teamOrders'] = $areaUsers->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'avatar_url' => $m->avatar_url,
                'authorized_sessions' => $allAreaAuthorizations->where('user_id', $m->id)->pluck('provider_daily_status_id'),
                'orders' => $teamOrders->where('user_id', $m->id)->map(fn($o) => [
                    'id' => $o->id, 'platillo' => $o->dailyMenu->name, 'provider' => $o->dailyMenu->provider->name,
                    'status' => $o->status, 'preferences' => $o->preferences, 'meal_type' => $o->meal_type,
                ])->values(),
            ])->values();

            $props['area'] = $user->area;
            $myOrdersToday = $teamOrders->where('user_id', $user->id);
            $props['myOrdersToday'] = $myOrdersToday->values();

            // Fetch order history grouped by session for the sidebar
            $recentOrders = Order::with(['dailyMenu.provider', 'user' => fn($q) => $q->withTrashed()])
                ->where('status', '!=', 'cancelled')
                ->whereHas('user', fn($q) => $q->withTrashed()->whereIn('area_id', $allMyAreaIds))
                ->latest()
                ->limit(100)
                ->get();

            $props['groupedHistory'] = $recentOrders->groupBy(function($o) {
                // Group by actual day of order and provider/meal
                return $o->created_at->toDateString() . '_' . $o->dailyMenu->provider_id . '_' . $o->meal_type;
            })->map(function($group) use ($user) {
                $first = $group->first();
                $date = $first->created_at->toDateString();
                
                // Find the session record
                $session = ProviderDailyStatus::where('date', $date)
                    ->where('provider_id', $first->dailyMenu->provider_id)
                    ->where('meal_type', $first->meal_type)
                    ->first();

                // NEW: Find area-specific evidence for THIS manager
                $areaEvidence = null;
                if ($session && $user->area_id) {
                    $areaEvidence = \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session->id)
                        ->where('area_id', $user->area_id)
                        ->first();
                }

                return [
                    'id' => $session?->id,
                    'date' => $date, 
                    'provider_name' => $first->dailyMenu->provider->name,
                    'provider_id' => $first->dailyMenu->provider_id,
                    'meal_type' => $first->meal_type,
                    'evidence_url' => $areaEvidence ? $areaEvidence->evidence_url : $session?->evidence_url,
                    'justified_count' => $group->whereNotNull('activity_performed')->where('activity_performed', '!=', '')->count(),
                    'total_orders' => $group->count(),
                    'orders' => $group->map(fn($o) => [
                        'id' => $o->id,
                        'user_name' => $o->user->name,
                        'avatar_url' => $o->user->avatar_url,
                        'platillo' => $o->dailyMenu->name,
                        'activity_performed' => $o->activity_performed,
                    ])->values(),
                ];
            })->values()->take(15);
            
            // Available Menus ONLY for authorized sessions (Diners) or Area Sessions (Managers)
            $visibleSessionIds = ($user->role === 'area_manager' || $user->role === 'admin' || $user->role === 'acquisitions_manager')
                ? $myAreaSessions->pluck('id')->map(fn($id) => (int)$id)->toArray()
                : array_map('intval', $userAuthorizations ?: []);

            $availableMenus = [];
            // Filter open sessions that are visible to this user
            $authorizedSessions = $openStatusesToday->filter(fn($s) => in_array((int)$s->id, $visibleSessionIds));
            
            foreach ($authorizedSessions as $session) {
                // IMPORTANT: Ensure is_open_for_my_area is TRUE for the frontend to show the menu
                $session->is_open_for_my_area = true;
                
                // PERMANENT CATALOG LOGIC: Get all published dishes for this provider
                // regardless of the date, ensuring they persist indefinately.
                $menus = DailyMenu::where('status', 'published')
                    ->where('provider_id', $session->provider->id)
                    ->with('provider')
                    ->get();
                
                foreach ($menus as $menu) {
                    $menu->meal_type = $session->meal_type;
                    $menu->already_ordered = $myOrdersToday->where('meal_type', $session->meal_type)->count() > 0;
                    $availableMenus[] = $menu;
                }
            }
            $props['availableMenus'] = collect($availableMenus)->values();

            // Pending authorizations notification for diners
            if ($user->role === 'diner') {
                $props['pendingAuthorizations'] = $myAreaSessions->filter(fn($s) => !in_array((int)$s->id, $userAuthorizations))->values();
            }

            // Override openSessions for Managers to show counts and compatibility flags
            if ($user->role !== 'acquisitions_manager' && $user->role !== 'admin') {
                $props['openSessions'] = $myAreaSessions->map(function($session) use ($allAreaAuthorizations) {
                    $session->authorized_count = $allAreaAuthorizations->where('provider_daily_status_id', $session->id)->count();
                    $session->is_open_for_my_area = true; // Compatibility with frontend computed props
                    return $session;
                })->values();
            }
        }

        return Inertia::render('Dashboard', $props);
    }


    public function showJustificationPage(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', $today);
        
        $props = [
            'userRole' => $user->role,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ];

        if ($user->role === 'area_manager' || $user->role === 'admin' || $user->role === 'acquisitions_manager') {
            $query = ProviderDailyStatus::whereBetween('date', [$startDate, $endDate])
                ->with('provider')
                ->latest('date');

            if ($user->role === 'area_manager') {
                $query->where(function($q) use ($user) {
                    $q->whereJsonContains('selected_area_ids', (int)$user->area_id);
                });
            }

            $historicalSessions = $query->get();

            $props['sessions'] = $historicalSessions->map(function($session) use ($user) {
                // 1. Get ONLY actual orders for this session context
                $ordersQuery = Order::where('meal_type', $session->meal_type)
                    ->whereDate('orders.created_at', $session->date)
                    ->where('orders.status', '!=', 'cancelled')
                    ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $session->provider_id))
                    ->with(['user.area', 'dailyMenu']);

                if ($user->role === 'area_manager') {
                    $ordersQuery->whereHas('user', fn($q) => $q->where('area_id', $user->area_id));
                }
                
                $orders = $ordersQuery->get();

                // 2. Group by Area
                $areasDetail = $orders->groupBy('user.area_id')->map(function($areaOrders, $areaId) use ($session) {
                    $area = $areaOrders->first()->user->area;
                    
                    $evidence = \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session->id)
                        ->where('area_id', $areaId)
                        ->first();

                    return [
                        'area_id' => $areaId,
                        'area_name' => $area->name ?? 'Sin Área',
                        'justified_count' => $areaOrders->whereNotNull('activity_performed')->where('activity_performed', '!=', '')->count(),
                        'total_orders' => $areaOrders->count(),
                        'evidence_url' => $evidence?->evidence_url,
                        'orders' => $areaOrders->map(fn($o) => [
                            'id' => $o->id,
                            'user_name' => $o->user->name,
                            'avatar_url' => $o->user->avatar_url,
                            'platillo' => $o->dailyMenu?->name,
                            'activity_performed' => $o->activity_performed,
                        ])->values(),
                    ];
                })->values();

                // Get names of ALL areas assigned to this session for the tags
                $assignedAreaIds = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                $assignedAreaNames = Area::whereIn('id', array_map('intval', $assignedAreaIds ?: []))->pluck('name');

                return [
                    'id' => $session->id,
                    'provider_id' => $session->provider->id,
                    'date' => $session->date,
                    'meal_type' => $session->meal_type,
                    'provider_name' => $session->provider->name,
                    'justified_count' => $orders->whereNotNull('activity_performed')->where('activity_performed', '!=', '')->count(), 
                    'total_orders' => $orders->count(),
                    'included_areas' => $assignedAreaNames,
                    'team_diner_names' => $orders->where('user.area_id', $user->area_id)->pluck('user.name')->values(), // NEW: My team members who ordered
                    'areas_detail' => $areasDetail,
                ];
            })->values();
        } 
        
        if ($user->role === 'diner' || $user->role === 'area_manager') {
            $orders = Order::where('user_id', $user->id)
                ->whereHas('dailyMenu', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('available_on', [$startDate, $endDate]);
                })
                ->with(['dailyMenu.provider'])
                ->latest()
                ->get();

            $props['orders'] = $orders->map(fn($o) => [
                'id' => $o->id,
                'date' => $o->dailyMenu->available_on,
                'meal_type' => $o->meal_type,
                'provider_name' => $o->dailyMenu->provider->name,
                'platillo' => $o->dailyMenu->name,
                'activity_performed' => $o->activity_performed,
            ])->values();
        }

        return Inertia::render('Admin/Orders/JustificationPage', $props);
    }

    public function showDailySummary(Request $request)
    {
        $user = $request->user();
        
        // Managers and Admins get Analytics instead of simple summary
        if (in_array($user->role, ['area_manager', 'admin', 'acquisitions_manager']) && $user->area_id) {
            return $this->showAreaHistory($request);
        }

        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        
        // --- Personal Stats ---
        $monthlyOrdersCount = Order::where('user_id', $user->id)
            ->whereHas('dailyMenu', fn($q) => $q->where('available_on', '>=', $startOfMonth))
            ->count();

        $favoriteDish = Order::where('user_id', $user->id)
            ->join('daily_menus', 'orders.daily_menu_id', '=', 'daily_menus.id')
            ->select('daily_menus.name', DB::raw('count(*) as total'))
            ->groupBy('daily_menus.name')
            ->orderByDesc('total')
            ->first();

        $justifiedOrders = Order::where('user_id', $user->id)
            ->whereNotNull('activity_performed')
            ->where('activity_performed', '!=', '')
            ->count();
        $totalOrdersAllTime = Order::where('user_id', $user->id)->count();
        $justificationRate = $totalOrdersAllTime > 0 ? round(($justifiedOrders / $totalOrdersAllTime) * 100) : 0;

        // --- Area Stats ---
        $areaParticipation = 0;
        if ($user->area_id) {
            $areaUsersCount = \App\Models\User::where('area_id', $user->area_id)->count();
            $areaOrdersToday = Order::whereHas('user', fn($q) => $q->where('area_id', $user->area_id))
                ->whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))
                ->distinct('user_id')
                ->count();
            $areaParticipation = $areaUsersCount > 0 ? round(($areaOrdersToday / $areaUsersCount) * 100) : 0;
        }

        // --- Global Stats ---
        $mealDistribution = Order::whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))
            ->select('meal_type', DB::raw('count(*) as total'))
            ->groupBy('meal_type')
            ->get()
            ->pluck('total', 'meal_type');

        // --- Activity Last 7 Days ---
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $count = Order::whereHas('dailyMenu', fn($q) => $q->where('available_on', $date))->count();
            $last7Days[] = [
                'day' => Carbon::today()->subDays($i)->isoFormat('ddd'),
                'count' => $count
            ];
        }

        return Inertia::render('DailySummary', [
            'stats' => [
                'personal' => [
                    'monthly_count' => $monthlyOrdersCount,
                    'favorite_dish' => $favoriteDish ? $favoriteDish->name : 'N/A',
                    'justification_rate' => $justificationRate,
                ],
                'area' => [
                    'participation_rate' => $areaParticipation,
                ],
                'system' => [
                    'meal_distribution' => $mealDistribution,
                    'weekly_activity' => $last7Days,
                ]
            ]
        ]);
    }

    public function showAreaHistory(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['area_manager', 'admin', 'acquisitions_manager']) || !$user->area_id) {
            abort(403);
        }

        $areaId = $user->area_id;
        
        // 1. General Stats for the Area
        $totalOrders = Order::whereHas('user', fn($q) => $q->where('area_id', $areaId))
            ->where('orders.status', '!=', 'cancelled')
            ->count();
            
        $justifiedOrders = Order::whereHas('user', fn($q) => $q->where('area_id', $areaId))
            ->where('orders.status', '!=', 'cancelled')
            ->whereNotNull('activity_performed')
            ->where('activity_performed', '!=', '')
            ->count();

        // 2. Top Dishes in this Area
        $topDishes = Order::whereHas('user', fn($q) => $q->where('area_id', $areaId))
            ->where('orders.status', '!=', 'cancelled')
            ->join('daily_menus', 'orders.daily_menu_id', '=', 'daily_menus.id')
            ->select('daily_menus.name', DB::raw('count(*) as total'))
            ->groupBy('daily_menus.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 3. Most Active Diners (Ranking)
        $topDiners = User::where('area_id', $areaId)
            ->withCount(['orders' => function($q) {
                $q->where('orders.status', '!=', 'cancelled');
            }])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        // 4. Orders by Provider
        $providerStats = Order::whereHas('user', fn($q) => $q->where('area_id', $areaId))
            ->where('orders.status', '!=', 'cancelled')
            ->join('daily_menus', 'orders.daily_menu_id', '=', 'daily_menus.id')
            ->join('providers', 'daily_menus.provider_id', '=', 'providers.id')
            ->select('providers.name', DB::raw('count(*) as total'))
            ->groupBy('providers.name')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Admin/Orders/AreaAnalytics', [
            'stats' => [
                'total_orders' => $totalOrders,
                'justification_rate' => $totalOrders > 0 ? round(($justifiedOrders / $totalOrders) * 100) : 0,
                'top_dishes' => $topDishes,
                'top_diners' => $topDiners,
                'provider_preferences' => $providerStats,
                'area_name' => $user->area?->name ?? 'Mi Dependencia'
            ]
        ]);
    }

    public function showAreaReports(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'area_manager') abort(403);

        $query = ProviderDailyStatus::where(function($q) use ($user) {
                $q->whereJsonContains('selected_area_ids', (int)$user->area_id);
            })
            ->with('provider');

        // Apply filters
        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->filled('meal_type')) {
            $query->where('meal_type', $request->meal_type);
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        $sessions = $query->latest('date')->get();

        return Inertia::render('Admin/Reports/AreaReports', [
            'sessions' => $sessions->values(),
            'area' => $user->area,
            'providers' => Provider::all(['id', 'name']),
            'filters' => $request->only(['start_date', 'end_date', 'meal_type', 'provider_id'])
        ]);
    }

    public function showGlobalHistory(Request $request)
    {
        $query = ProviderDailyStatus::with(['provider', 'provider.dailyMenus'])
            ->where('status', 'closed');

        if ($request->filled('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        $sessions = $query->latest('date')->paginate(12)->withQueryString();

        return Inertia::render('Admin/Orders/GlobalHistory', [
            'sessions' => $sessions,
            'providers' => Provider::all(['id', 'name']),
            'filters' => $request->only(['start_date', 'end_date', 'provider_id'])
        ]);
    }

    public function showGlobalReports(Request $request)
    {
        // Stats by Provider (always calculated for the overview)
        $providerStats = Provider::withCount('dailyMenus')->get()->map(function($provider) {
            $totalOrders = Order::whereHas('dailyMenu', function($q) use ($provider) {
                $q->where('provider_id', $provider->id);
            })->count();

            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'total_orders' => $totalOrders,
                'dish_count' => $provider->daily_menus_count,
                'most_popular_meal' => Order::whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id))
                    ->select('meal_type', DB::raw('count(*) as total'))
                    ->groupBy('meal_type')
                    ->orderByDesc('total')
                    ->first()?->meal_type ?? 'N/A'
            ];
        })->sortByDesc('total_orders')->values();

        // Main orders query with filters
        $ordersQuery = Order::with(['user.area', 'dailyMenu.provider'])->latest();

        if ($request->filled('area_id')) {
            $ordersQuery->whereHas('user', fn($q) => $q->where('area_id', $request->area_id));
        }
        if ($request->filled('provider_id')) {
            $ordersQuery->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $request->provider_id));
        }
        if ($request->filled('meal_type')) {
            $ordersQuery->where('meal_type', $request->meal_type);
        }
        if ($request->filled('start_date')) {
            $ordersQuery->whereHas('dailyMenu', fn($q) => $q->where('available_on', '>=', $request->start_date));
        }
        if ($request->filled('end_date')) {
            $ordersQuery->whereHas('dailyMenu', fn($q) => $q->where('available_on', '<=', $request->end_date));
        }

        return Inertia::render('Admin/Reports/GlobalReports', [
            'providerStats' => $providerStats,
            'orders' => $ordersQuery->paginate(20)->withQueryString(),
            'areas' => Area::all(['id', 'name']),
            'providers' => Provider::all(['id', 'name']),
            'filters' => $request->only(['area_id', 'provider_id', 'meal_type', 'start_date', 'end_date'])
        ]);
    }

    public function activateMenu(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:open'],
            'selected_area_ids' => ['required', 'array', 'min:1'],
            'selected_area_ids.*' => ['integer', 'exists:areas,id'],
            'meal_type' => ['required', 'string', 'max:50'],
            'conflict_resolution' => ['nullable', 'string', 'in:merge,substitute,restart'], 
        ]);

        $mealType = $validated['meal_type'];
        $date = $validated['date'];
        $newAreaIds = array_map('intval', $validated['selected_area_ids']);

        // FIXED: Check if there are published dishes for this provider (Permanent catalog logic)
        $dishesCount = DailyMenu::where('provider_id', $provider->id)
                                ->where('status', 'published')
                                ->count();
        
        if ($dishesCount === 0) {
            return back()->withErrors([
                'error' => "No puedes activar el servicio para '{$provider->name}' porque no tiene platillos publicados en su catálogo. Agrega y publica platillos primero."
            ]);
        }

        $existingOtherSessions = ProviderDailyStatus::where('date', $date)
            ->where('meal_type', $mealType)
            ->where('provider_id', '!=', $provider->id)
            ->with('provider')
            ->get();

        foreach ($existingOtherSessions as $otherSession) {
            $otherAreaIds = is_array($otherSession->selected_area_ids) 
                ? $otherSession->selected_area_ids 
                : json_decode($otherSession->selected_area_ids, true);
            $otherAreaIds = array_map('intval', $otherAreaIds ?: []);

            $conflicts = array_intersect($newAreaIds, $otherAreaIds);

            if (count($conflicts) > 0) {
                if ($request->conflict_resolution === 'substitute' || $request->conflict_resolution === 'restart') {
                    $remainingAreas = array_values(array_diff($otherAreaIds, $conflicts));
                    $otherSession->update(['selected_area_ids' => $remainingAreas]);
                    
                    // ALWAYS clean up authorizations and orders for conflicting areas when "moving" them to a new provider
                    // regardless of explicit 'restart' if we are substituting, because the menu context is completely different.
                    
                    // 1. Remove session authorizations for users in conflicting areas for the OLD session
                    SessionAuthorization::where('provider_daily_status_id', $otherSession->id)
                        ->whereHas('user', function($q) use ($conflicts) {
                            $q->whereIn('area_id', $conflicts);
                        })->delete();

                    // 2. Mark orders as cancelled for users in conflicting areas for the OLD session context
                    Order::where('meal_type', $mealType)
                        ->whereDate('orders.created_at', $otherSession->date)
                        ->whereHas('dailyMenu', function($q) use ($otherSession) {
                            $q->where('provider_id', $otherSession->provider->id);
                        })
                        ->whereHas('user', function($q) use ($conflicts) {
                            $q->whereIn('area_id', $conflicts);
                        })->update(['status' => 'cancelled']);
                }
            }
        }

        $existingSession = ProviderDailyStatus::where('provider_id', $provider->id)
            ->where('date', $date)
            ->where('meal_type', $mealType)
            ->first();

        $finalAreaIds = $newAreaIds;
        if ($existingSession && $request->conflict_resolution === 'merge') {
            $currentAreaIds = is_array($existingSession->selected_area_ids) 
                ? $existingSession->selected_area_ids 
                : json_decode($existingSession->selected_area_ids, true);
            $currentAreaIds = array_map('intval', $currentAreaIds ?: []);
            $finalAreaIds = array_values(array_unique(array_merge($currentAreaIds, $newAreaIds)));
        }

        ProviderDailyStatus::updateOrCreate(
            [
                'provider_id' => $provider->id, 
                'date' => $date,
                'meal_type' => $mealType
            ],
            [
                'status' => 'open',
                'selected_area_ids' => $finalAreaIds,
                'activated_at' => $existingSession ? $existingSession->activated_at : now(),
            ]
        );

        $newSession = ProviderDailyStatus::where('provider_id', $provider->id)->where('date', $date)->where('meal_type', $mealType)->first();

        // NEW: Auto-authorize the Managers of the selected areas
        if ($newSession) {
            $managers = \App\Models\User::whereIn('area_id', $finalAreaIds)
                ->whereIn('role', ['area_manager', 'admin', 'acquisitions_manager'])
                ->get();
            
            foreach ($managers as $manager) {
                \App\Models\SessionAuthorization::updateOrCreate([
                    'provider_daily_status_id' => $newSession->id,
                    'user_id' => $manager->id,
                ], [
                    'authorized_by_user_id' => auth()->id() // Authorized by the one who opened the session
                ]);
            }
        }

        return redirect()->route('dashboard')->with('success', "Gestión de sesión completada.");
    }

    public function deactivateMenu(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'meal_type' => ['required', 'string'],
        ]);

        $providerDailyStatus = ProviderDailyStatus::where('provider_id', $provider->id)
                                                  ->where('date', $validated['date'])
                                                  ->where('meal_type', $validated['meal_type'])
                                                  ->first();

        if ($providerDailyStatus) {
            $providerDailyStatus->update(['status' => 'closed']);
        }

        return redirect()->route('admin.orders.summary', [
            'provider' => $provider->id,
            'date' => $validated['date'],
            'meal_type' => $validated['meal_type']
        ])->with('success', 'Menú desactivado exitosamente.');
    }

    public function destroySession(Request $request, ProviderDailyStatus $session)
    {
        $validated = $request->validate(['reason' => 'required|string|max:500']);

        DB::table('session_deletion_logs')->insert([
            'user_id' => auth()->id(),
            'provider_name' => $session->provider->name,
            'meal_type' => $session->meal_type,
            'date' => $session->date,
            'reason' => $validated['reason'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Remove authorizations for this session
        SessionAuthorization::where('provider_daily_status_id', $session->id)->delete();

        // 2. Remove orders associated with this session context
        Order::where('meal_type', $session->meal_type)
            ->whereDate('orders.created_at', $session->date)
            ->whereHas('dailyMenu', function($q) use ($session) {
                $q->where('provider_id', $session->provider->id);
            })->delete();

        $session->delete();
        return redirect()->route('dashboard')->with('success', 'Sesión eliminada correctamente.');
    }

    public function updateSessionAreas(Request $request, ProviderDailyStatus $session)
    {
        $validated = $request->validate([
            'selected_area_ids' => 'required|array',
            'selected_area_ids.*' => 'integer|exists:areas,id',
        ]);

        $oldAreaIds = $session->selected_area_ids ?? [];
        $newAreaIds = array_map('intval', $validated['selected_area_ids']);
        
        // Find areas being removed
        $removedAreaIds = array_diff($oldAreaIds, $newAreaIds);

        if (!empty($removedAreaIds)) {
            // 1. Remove session authorizations for users in removed areas
            SessionAuthorization::where('provider_daily_status_id', $session->id)
                ->whereHas('user', function($q) use ($removedAreaIds) {
                    $q->whereIn('area_id', $removedAreaIds);
                })->delete();

            // 2. Mark orders as cancelled for users in removed areas for this specific session context
            Order::where('meal_type', $session->meal_type)
                ->whereDate('orders.created_at', $session->date)
                ->whereHas('dailyMenu', function($q) use ($session) {
                    $q->where('provider_id', $session->provider->id);
                })
                ->whereHas('user', function($q) use ($removedAreaIds) {
                    $q->whereIn('area_id', $removedAreaIds);
                })->update(['status' => 'cancelled']);
        }

        $session->update(['selected_area_ids' => $newAreaIds]);
        
        return redirect()->route('dashboard')->with('success', 'Áreas actualizadas y datos de pedidos/autorizaciones limpiados.');
    }

    public function showOrderSummary(Provider $provider, string $date, Request $request, $meal_type = null)
    {
        // Prioritize: 1. Route Parameter, 2. Query Parameter, 3. Default 'Comida'
        $mealType = $meal_type ?? $request->query('meal_type', 'Comida');
        
        $orders = Order::where('meal_type', $mealType)
            ->whereDate('orders.created_at', $date)
            ->where('status', '!=', 'cancelled')
            ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id))
            ->with(['user' => fn($q) => $q->withTrashed(), 'user.area', 'dailyMenu'])->get();

        $summary = $orders->groupBy('user.area.name')->map(fn($areaOrders, $areaName) => [
            'area_name' => $areaName,
            'area_id' => $areaOrders->first()->user->area->id, 
            'platillos' => $areaOrders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => [
                'platillo_name' => $pName,
                'total_count' => $pOrders->count(), 
                'observations' => $pOrders->pluck('preferences')->filter()->values(), 
            ])->values()->sortBy('platillo_name')->values(),
            'individual_orders' => $areaOrders->map(fn($order) => [
                'user_name' => $order->user->name,
                'avatar_url' => $order->user->avatar_url,
                'platillo_name' => $order->dailyMenu->name,
                'platillo_description' => $order->dailyMenu->description,
                'preferences' => $order->preferences,
                'activity_performed' => $order->activity_performed,
            ]),
            'total_area_orders' => $areaOrders->count(),
        ])->values()->sortBy('area_name')->values();

        return Inertia::render('Admin/Orders/Summary', [
            'provider' => $provider,
            'date' => $date,
            'mealType' => $mealType,
            'ordersSummary' => $summary,
            'reportConfig' => json_decode(SystemSetting::where('key', 'report_configuration')->first()?->value ?: '{}', true),
            'whatsappConfig' => json_decode(SystemSetting::where('key', 'whatsapp_configuration')->first()?->value ?: '{}', true),
        ]);
    }

    public function showSendOrderView(Provider $provider, string $date, Request $request, $meal_type = null)
    {
        $mealType = $meal_type ?? $request->query('meal_type', 'Comida');

        $orders = Order::where('meal_type', $mealType)
            ->whereDate('orders.created_at', $date)
            ->whereHas('dailyMenu', function ($query) use ($provider) {
                $query->where('provider_id', $provider->id);
            })
            ->where('status', 'submitted_by_manager')
            ->with(['user.area', 'dailyMenu'])
            ->get();

        $list = $orders->map(fn($o) => [
            'area' => $o->user->area->name,
            'platillo' => $o->dailyMenu->name,
            'comensal' => $o->user->name,
            'preferences' => $o->preferences
        ]);

        return Inertia::render('Admin/Orders/SendOrder', [
            'provider' => $provider,
            'date' => $date,
            'mealType' => $mealType,
            'orders' => $list,
        ]);
    }

    public function exportGlobalReports(Request $request)
    {
        $format = $request->query('format', 'pdf');
        
        $query = Order::with(['user.area', 'dailyMenu.provider'])->latest();

        if ($request->filled('area_id')) {
            $query->whereHas('user', fn($q) => $q->where('area_id', $request->area_id));
        }
        if ($request->filled('provider_id')) {
            $query->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $request->provider_id));
        }
        if ($request->filled('meal_type')) {
            $query->where('meal_type', $request->meal_type);
        }
        if ($request->filled('start_date')) {
            $query->whereHas('dailyMenu', fn($q) => $q->where('available_on', '>=', $request->start_date));
        }
        if ($request->filled('end_date')) {
            $query->whereHas('dailyMenu', fn($q) => $q->where('available_on', '<=', $request->end_date));
        }

        $orders = $query->get();

        // Standardize data for the view
        $summary = $orders->groupBy('user.area.name')->map(fn($aOrders, $aName) => [
            'group_name' => $aName,
            'total_count' => $aOrders->count(),
            'platillos' => $aOrders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => [
                'platillo_name' => $pName, 
                'total_count' => $pOrders->count(), 
                'observations' => $pOrders->pluck('preferences')->filter()->values()
            ])->values()->sortBy('platillo_name')->values(),
            'individual_orders' => $aOrders->sortBy('user.name')->map(fn($o) => [
                'user_name' => $o->user->name, 
                'avatar_url' => $o->user->avatar_url, 
                'area_name' => $o->user->area->name, 
                'platillo_name' => $o->dailyMenu->name, 
                'provider_name' => $o->dailyMenu->provider->name,
                'preferences' => $o->preferences, 
                'activity_performed' => $o->activity_performed,
                'date' => $o->dailyMenu->available_on,
                'meal_type' => $o->meal_type
            ]),
        ])->sortKeys();

        $data = [
            'provider' => (object)['name' => 'Reporte Consolidado'],
            'date' => $request->start_date ?: Carbon::today()->toDateString(),
            'mealType' => $request->meal_type ?: 'General',
            'ordersSummary' => $summary->values(),
            'sortBy' => 'area',
            'viewMode' => 'detailed',
            'isSingleArea' => $request->filled('area_id')
        ];

        $filename = "reporte_global_" . now()->format('Ymd_His');

        if ($format === 'excel') return $this->exportToExcel($data, $filename);
        
        if ($format === 'word') {
            return response()->view('reports.orders_summary', $data)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', "attachment; filename={$filename}.doc");
        }

        $pdf = Pdf::loadView('reports.orders_summary', $data);
        return $pdf->stream("{$filename}.pdf");
    }

    public function generatePdfReport(Provider $provider, string $date, Request $request, $meal_type = null)
    {
        try {
            $reportConfig = json_decode(SystemSetting::where('key', 'report_configuration')->first()?->value ?: '{}', true);
            $mealType = $meal_type ?? $request->query('meal_type', 'Comida');
            $areaId = $request->query('area_id');
            $sortBy = $request->query('sort', $reportConfig['default_sort'] ?? 'area'); 
            $viewMode = $request->query('view_mode', 'detailed'); 
            $format = $request->query('format', 'pdf');

            $query = Order::where('meal_type', $mealType)
                ->whereDate('orders.created_at', $date)
                ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id))
                ->with(['user.area', 'dailyMenu']);

            if ($areaId) $query->whereHas('user', fn($q) => $q->where('area_id', $areaId));
            $orders = $query->get();

            // Find the current session to link evidence
            $session = ProviderDailyStatus::where('date', $date)
                ->where('provider_id', $provider->id)
                ->where('meal_type', $mealType)
                ->first();

            // Helper to convert to base64
            $toBase64 = function($path) {
                if (!$path || !file_exists($path)) return null;
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            };

            if ($sortBy === 'platillo') {
                $summary = $orders->groupBy('dailyMenu.name')->map(function($pOrders, $pName) use ($session, $areaId, $toBase64) {
                    $evidencePath = null;
                    if ($areaId) {
                        $evidence = \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session?->id)
                            ->where('area_id', $areaId)->first();
                        $evidencePath = $evidence && $evidence->evidence_image ? $toBase64(public_path('storage/' . $evidence->evidence_image)) : null;
                    }

                    return [
                        'group_name' => $pName,
                        'total_count' => $pOrders->count(),
                        'evidence_path' => $evidencePath,
                        'platillos' => [['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()]],
                        'individual_orders' => $pOrders->sortBy('user.name')->map(fn($o) => [
                            'user_name' => $o->user->name, 
                            'avatar_url' => $o->user->avatar ? $toBase64(public_path('storage/' . $o->user->avatar)) : null,
                            'area_name' => $o->user->area->name, 
                            'platillo_name' => $o->dailyMenu->name, 
                            'preferences' => $o->preferences, 
                            'activity_performed' => $o->activity_performed
                        ]),
                    ];
                })->sortKeys();
            } elseif ($sortBy === 'name') {
                $evidencePath = null;
                if ($areaId) {
                    $evidence = \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session?->id)
                        ->where('area_id', $areaId)->first();
                    $evidencePath = $evidence && $evidence->evidence_image ? $toBase64(public_path('storage/' . $evidence->evidence_image)) : null;
                }

                $summary = collect(['Listado General' => [
                    'group_name' => 'Listado Alfabético General',
                    'total_count' => $orders->count(),
                    'evidence_path' => $evidencePath,
                    'platillos' => $orders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => ['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()])->values()->sortBy('platillo_name')->values(),
                    'individual_orders' => $orders->sortBy('user.name')->map(fn($o) => [
                        'user_name' => $o->user->name, 
                        'avatar_url' => $o->user->avatar ? $toBase64(public_path('storage/' . $o->user->avatar)) : null,
                        'area_name' => $o->user->area->name, 
                        'platillo_name' => $o->dailyMenu->name, 
                        'preferences' => $o->preferences, 
                        'activity_performed' => $o->activity_performed
                    ]),
                ]]);
            } else {
                $summary = $orders->groupBy('user.area.name')->map(function($aOrders, $aName) use ($session, $toBase64) {
                    $areaId = $aOrders->first()->user->area_id;
                    $evidence = $session ? \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session->id)
                        ->where('area_id', $areaId)->first() : null;

                    return [
                        'group_name' => $aName,
                        'total_count' => $aOrders->count(),
                        'evidence_path' => $evidence && $evidence->evidence_image ? $toBase64(public_path('storage/' . $evidence->evidence_image)) : null,
                        'platillos' => $aOrders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => ['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()])->values()->sortBy('platillo_name')->values(),
                        'individual_orders' => $aOrders->sortBy('user.name')->map(fn($o) => [
                            'user_name' => $o->user->name, 
                            'avatar_url' => $o->user->avatar ? $toBase64(public_path('storage/' . $o->user->avatar)) : null,
                            'area_name' => $o->user->area->name, 
                            'platillo_name' => $o->dailyMenu->name, 
                            'preferences' => $o->preferences, 
                            'activity_performed' => $o->activity_performed
                        ]),
                    ];
                })->sortKeys();
            }

            $data = [
                'provider' => $provider, 
                'date' => $date, 
                'mealType' => $mealType, 
                'ordersSummary' => $summary->values(), 
                'sortBy' => $sortBy, 
                'viewMode' => $viewMode, 
                'isSingleArea' => !empty($areaId),
                'isPdf' => ($format === 'pdf'),
                'reportConfig' => json_decode(SystemSetting::where('key', 'report_configuration')->first()?->value ?: '{}', true),
            ];

            $filename = "reporte_{$mealType}_{$provider->name}_$date";

            if ($format === 'excel') {
                return $this->exportToExcel($data, $filename);
            }

            if ($format === 'word') {
                return response()->view('reports.orders_summary', $data)
                    ->header('Content-Type', 'application/msword')
                    ->header('Content-Disposition', "attachment; filename={$filename}.doc");
            }

            // Default: PDF
            $pdf = Pdf::loadView('reports.orders_summary', $data);
            return $pdf->stream("{$filename}.pdf");
        } catch (\Exception $e) {
            Log::error("Error generating report: " . $e->getMessage());
            return "Error al generar el reporte: " . $e->getMessage();
        }
    }

    public function generateExpedienteReport(Provider $provider, string $date, Request $request, $meal_type = null)
    {
        try {
            $mealType = $meal_type ?? $request->query('meal_type', 'Comida');
            $areaId = $request->query('area_id');
            $format = $request->query('format', 'pdf');

            // 1. GATHER ALL DATA
            $query = Order::where('meal_type', $mealType)
                ->whereDate('orders.created_at', $date)
                ->where('status', '!=', 'cancelled')
                ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id))
                ->with(['user' => fn($q) => $q->withTrashed(), 'user.area', 'dailyMenu']);

            if ($areaId) $query->whereHas('user', fn($q) => $q->withTrashed()->where('area_id', $areaId));
            $orders = $query->get();

            $session = ProviderDailyStatus::where('date', $date)
                ->where('provider_id', $provider->id)
                ->where('meal_type', $mealType)
                ->first();

            // 2. CONVERT ASSETS TO BASE64 (Required for DOMPDF)
            $toBase64 = function($path) {
                if (!$path || !file_exists($path)) return null;
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            };

            $logoPath = public_path('logo.png');
            $logoBase64 = $toBase64($logoPath);

            // 3. GROUP DATA BY AREA (Dossier Format)
            $summary = $orders->groupBy('user.area.name')->map(function($aOrders, $aName) use ($session, $toBase64) {
                $areaId = $aOrders->first()->user->area_id;
                $evidence = $session ? \App\Models\AreaSessionStatus::where('provider_daily_status_id', $session->id)
                    ->where('area_id', $areaId)->first() : null;

                return [
                    'group_name' => $aName,
                    'total_count' => $aOrders->count(),
                    'evidence_path' => ($evidence && $evidence->evidence_image) ? $toBase64(public_path('storage/' . $evidence->evidence_image)) : null,
                    'individual_orders' => $aOrders->sortBy('user.name')->map(fn($o) => [
                        'user_name' => $o->user->name, 
                        'area_name' => $o->user->area->name ?? 'N/A', 
                        'platillo_name' => $o->dailyMenu->name, 
                        'activity_performed' => $o->activity_performed
                    ]),
                ];
            })->sortKeys();

            $data = [
                'provider' => $provider,
                'date' => $date,
                'mealType' => $mealType,
                'session' => $session,
                'ordersSummary' => $summary->values(),
                'totalOrders' => $orders->count(),
                'logo_base64' => $logoBase64,
            ];

            $filename = "expediente_{$mealType}_{$provider->name}_$date";

            if ($format === 'excel') {
                return $this->exportExpedienteToExcel($data, $filename);
            }

            if ($format === 'word') {
                return response()->view('reports.expediente', $data)
                    ->header('Content-Type', 'application/msword')
                    ->header('Content-Disposition', "attachment; filename={$filename}.doc");
            }

            $pdf = Pdf::loadView('reports.expediente', $data);
            return $pdf->stream("{$filename}.pdf");

        } catch (\Exception $e) {
            Log::error("Error generating dossier: " . $e->getMessage());
            return "Error al generar el expediente digital: " . $e->getMessage();
        }
    }

    /**
     * Helper to export the comprehensive dossier data to Excel/CSV.
     */
    private function exportExpedienteToExcel($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // UTF-8 BOM

            // 1. CARATULA
            fputcsv($file, ['EXPEDIENTE DIGITAL DE ALIMENTACIÓN - SICOA']);
            fputcsv($file, ['Fecha de Servicio:', $data['date']]);
            fputcsv($file, ['Tipo de Alimento:', strtoupper($data['mealType'])]);
            fputcsv($file, ['Proveedor:', $data['provider']->name]);
            fputcsv($file, ['Total Pedidos:', $data['totalOrders']]);
            fputcsv($file, []); // Empty

            // 2. CUERPO DE DATOS
            fputcsv($file, ['#', 'ÁREA/DEPENDENCIA', 'NOMBRE DEL COMENSAL', 'PLATILLO SOLICITADO', 'JUSTIFICACIÓN / ACTIVIDAD']);
            
            $count = 1;
            foreach ($data['ordersSummary'] as $group) {
                foreach ($group['individual_orders'] as $o) {
                    fputcsv($file, [
                        $count++,
                        $group['group_name'],
                        $o['user_name'],
                        $o['platillo_name'],
                        $o['activity_performed'] ?: 'SIN JUSTIFICACIÓN'
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}.csv",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fputs($file, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

            fputcsv($file, ['REPORTE DE PEDIDOS - ' . strtoupper($data['mealType'])]);
            fputcsv($file, ['Proveedor:', $data['provider']->name]);
            fputcsv($file, ['Fecha:', $data['date']]);
            fputcsv($file, []); // Empty line

            if ($data['viewMode'] === 'dishes') {
                fputcsv($file, ['GRUPO', 'PLATILLO', 'CANTIDAD', 'OBSERVACIONES']);
                foreach ($data['ordersSummary'] as $group) {
                    foreach ($group['platillos'] as $p) {
                        fputcsv($file, [
                            $group['group_name'],
                            $p['platillo_name'],
                            $p['total_count'],
                            implode(' | ', $p['observations'])
                        ]);
                    }
                }
            } else {
                fputcsv($file, ['GRUPO', 'COMENSAL', 'PLATILLO', 'OBSERVACIONES', 'JUSTIFICACION']);
                foreach ($data['ordersSummary'] as $group) {
                    foreach ($group['individual_orders'] as $o) {
                        fputcsv($file, [
                            $group['group_name'],
                            $o['user_name'],
                            $o['platillo_name'],
                            $o['preferences'],
                            $o['activity_performed']
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function showDeletionLogs(Request $request)
    {
        $logs = DB::table('session_deletion_logs')
            ->join('users', 'session_deletion_logs.user_id', '=', 'users.id')
            ->select('session_deletion_logs.*', 'users.name as user_name')
            ->orderByDesc('session_deletion_logs.created_at')
            ->paginate(20);

        return Inertia::render('Admin/Sessions/DeletionLogs', [
            'logs' => $logs
        ]);
    }

    public function uploadEvidence(Request $request, ProviderDailyStatus $session)
    {
        $request->validate([
            'image' => 'required|image|max:20480', // Increased to 20MB for mobile photos
            'area_id' => 'nullable|exists:areas,id'
        ]);

        $areaId = $request->area_id ?? $request->user()->area_id;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('evidence', 'public');
            
            if ($areaId) {
                // Save specific for area
                $status = \App\Models\AreaSessionStatus::updateOrCreate([
                    'provider_daily_status_id' => $session->id,
                    'area_id' => $areaId
                ], [
                    'evidence_image' => $path
                ]);
            } else {
                // Global session fallback
                $session->update(['evidence_image' => $path]);
            }
        }

        return back()->with('success', 'Evidencia subida correctamente.');
    }
}
