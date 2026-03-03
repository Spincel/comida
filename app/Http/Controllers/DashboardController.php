<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\ProviderDailyStatus;
use App\Models\Area;
use App\Models\Order;
use App\Models\DailyMenu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $props = ['userRole' => $user->role];

        // 1. Get ALL sessions for today (Base for filtering)
        $allSessionsToday = ProviderDailyStatus::where('date', $today)->with('provider')->get();
        $openStatusesToday = $allSessionsToday->where('status', 'open')->values();
        $props['closedTodaySessions'] = $allSessionsToday->where('status', 'closed')->values();
        $props['allSessionsToday'] = $allSessionsToday->values();

        // 2. Identify sessions relevant to the user's area
        $myAreaSessions = collect();
        if ($user->area_id) {
            $myAreaSessions = $openStatusesToday->filter(function($session) use ($user) {
                $selectedAreaIds = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                $selectedAreaIds = array_map('intval', $selectedAreaIds ?: []);
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
                    ->whereHas('dailyMenu', function($q) use ($session, $today) {
                        $q->where('provider_id', $session->provider_id)
                          ->where('available_on', $today);
                    })->with('user')->get();

                $session->areas_status = $allAreas->whereIn('id', $selectedAreaIds)->map(fn($area) => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'is_submitted' => $sessionOrders->where('user.area_id', $area->id)->where('status', 'submitted_by_manager')->count() > 0 && $sessionOrders->where('user.area_id', $area->id)->where('status', 'submitted_by_user')->count() == 0,
                    'is_pending' => $sessionOrders->where('user.area_id', $area->id)->where('status', 'submitted_by_user')->count() > 0,
                    'order_count' => $sessionOrders->where('user.area_id', $area->id)->count(),
                    'submitted_count' => $sessionOrders->where('user.area_id', $area->id)->where('status', 'submitted_by_manager')->count(),
                ])->values();

                return $session;
            });

            $props['providers'] = $providers;
            $props['areas'] = $allAreas; 
            $props['openSessions'] = $openSessionsDetailed->values();
            $props['allOpenSessionsToday'] = $openStatusesToday; 

            // Active orders summary for acquisitions monitor
            $activeOrders = Order::where('status', 'submitted_by_manager')
                ->whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))
                ->with(['user.area', 'dailyMenu'])
                ->get();

            $props['dishSummaryToday'] = $activeOrders->groupBy(fn($o) => $o->meal_type . '_' . $o->dailyMenu->provider_id)
                ->map(fn($mealOrders) => [
                    'meal_type' => $mealOrders->first()->meal_type,
                    'provider_id' => $mealOrders->first()->dailyMenu->provider_id,
                    'total' => $mealOrders->count(),
                    'dishes' => $mealOrders->groupBy('dailyMenu.name')->map(fn($orders, $name) => ['name' => $name, 'count' => $orders->count()])->values()->sortByDesc('count')->values(),
                ])->values();
        }

        // 4. Logic for AREA MANAGER / DINER (and Acquisitions with area)
        if ($user->area_id) {
            $areaUsers = \App\Models\User::where('area_id', $user->area_id)->get();
            $areaUserIds = $areaUsers->pluck('id');

            // Authorizations ONLY for sessions that include my area
            $allAreaAuthorizations = \App\Models\SessionAuthorization::whereIn('user_id', $areaUserIds)
                ->whereIn('provider_daily_status_id', $myAreaSessions->pluck('id'))
                ->get();

            $teamOrders = Order::whereIn('user_id', $areaUserIds)
                                ->whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))
                                ->with(['user', 'dailyMenu.provider'])
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
            $props['myOrdersToday'] = $teamOrders->where('user_id', $user->id)->values();
            
            // Available Menus ONLY for sessions that include my area
            $props['availableMenus'] = DailyMenu::where('available_on', $today)
                ->where('status', 'published')
                ->whereIn('provider_id', $myAreaSessions->pluck('provider_id'))
                ->whereIn('meal_type', $myAreaSessions->pluck('meal_type'))
                ->with('provider')
                ->get();

            // Override openSessions for Managers to ONLY show what's for their area
            if ($user->role !== 'acquisitions_manager' && $user->role !== 'admin') {
                $props['openSessions'] = $myAreaSessions->map(function($session) use ($allAreaAuthorizations) {
                    $session->authorized_count = $allAreaAuthorizations->where('provider_daily_status_id', $session->id)->count();
                    return $session;
                })->values();
            }
        }

        return Inertia::render('Dashboard', $props);
    }
                    ->toArray();

                $availableSessionsForArea = $openStatuses->filter(function($session) use ($user, $userAuthorizations) {
                    $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                    $isAreaSelected = in_array((int)$user->area_id, array_map('intval', $areas ?: []));
                    
                    // NEW: Admins and Acquisitions are always authorized for sessions open to their area
                    return $isAreaSelected;
                })->values();

                $props['pendingAuthorizations'] = $openStatuses->filter(function($session) use ($user, $userAuthorizations) {
                    $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                    $isAreaSelected = in_array((int)$user->area_id, array_map('intval', $areas ?: []));
                    // Check if not authorized yet (for UI notification only)
                    return $isAreaSelected && !in_array((int)$session->id, $userAuthorizations);
                })->values();

                $availableMenus = [];
                foreach ($availableSessionsForArea as $session) {
                    $menus = DailyMenu::where('provider_id', $session->provider_id)
                        ->where('available_on', $today)
                        ->where('status', 'published')
                        ->with('provider')
                        ->get();
                    foreach ($menus as $menu) {
                        $menu->meal_type = $session->meal_type;
                        $menu->already_ordered = $props['myOrdersToday']->where('meal_type', $session->meal_type)->count() > 0;
                        $availableMenus[] = $menu;
                    }
                }
                $props['availableMenus'] = $availableMenus;
                $props['activeMealTypes'] = $openStatuses->filter(function($session) use ($user) {
                    $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                    return in_array((int)$user->area_id, array_map('intval', $areas ?: []));
                })->pluck('meal_type')->unique()->values();
            }

        } elseif ($user->role === 'area_manager') {
            // ONLY sessions where THIS manager's area is selected
            $myAreaSessions = $openStatuses->filter(fn($s) => $s->is_open_for_my_area);
            $activeMealTypes = $myAreaSessions->pluck('meal_type')->unique()->values();
            
            $areaUsers = \App\Models\User::where('area_id', $user->area_id)->get();
            
            // Get ALL authorizations for this area's users today
            $allAreaAuthorizations = \App\Models\SessionAuthorization::whereIn('user_id', $areaUsers->pluck('id'))
                ->whereIn('provider_daily_status_id', $myAreaSessions->pluck('id'))
                ->get();

            $todayOrders = Order::whereIn('user_id', $areaUsers->pluck('id'))
                                ->whereHas('dailyMenu', function ($query) use ($today) {
                                    $query->where('available_on', $today);
                                })
                                ->with(['user', 'dailyMenu.provider'])
                                ->get();
            
            $props['teamOrders'] = $areaUsers->map(function($teamMember) use ($todayOrders, $allAreaAuthorizations) {
                return [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'avatar_url' => $teamMember->avatar_url,
                    'authorized_sessions' => $allAreaAuthorizations->where('user_id', $teamMember->id)->pluck('provider_daily_status_id'),
                    'orders' => $todayOrders->where('user_id', $teamMember->id)->map(fn($o) => [
                        'id' => $o->id,
                        'platillo' => $o->dailyMenu->name,
                        'provider' => $o->dailyMenu->provider->name,
                        'status' => $o->status,
                        'preferences' => $o->preferences,
                        'meal_type' => $o->meal_type,
                    ])->values(),
                ];
            })->values();
            $props['area'] = $user->area;
            $props['activeMealTypes'] = $activeMealTypes;
            $props['openSessions'] = $myAreaSessions->map(function($session) use ($user, $allAreaAuthorizations) {
                // Add count of authorized users for this session in this area
                $session->authorized_count = $allAreaAuthorizations->where('provider_daily_status_id', $session->id)->count();
                return $session;
            })->values();

            // Diner data for Area Manager
            $myOrdersToday = $todayOrders->where('user_id', $user->id);
            $props['myOrdersToday'] = $myOrdersToday->values();
            
            $availableSessionsForArea = $openStatuses->filter(function($session) use ($user) {
                $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                // Managers are always authorized for sessions open for their area
                return in_array((int)$user->area_id, array_map('intval', $areas ?: []));
            })->values();

            $availableMenus = [];
            foreach ($availableSessionsForArea as $session) {
                $menus = DailyMenu::where('provider_id', $session->provider_id)
                    ->where('available_on', $today)
                    ->where('status', 'published')
                    ->with('provider')
                    ->get();
                foreach ($menus as $menu) {
                    $menu->meal_type = $session->meal_type;
                    $menu->already_ordered = $myOrdersToday->where('meal_type', $session->meal_type)->count() > 0;
                    $availableMenus[] = $menu;
                }
            }
            $props['availableMenus'] = $availableMenus;
            $props['orderHistory'] = Order::where('user_id', $user->id)->with('dailyMenu.provider')->latest()->take(5)->get();

        } elseif ($user->role === 'diner') {
            $myOrdersToday = Order::where('user_id', $user->id)->whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))->with('dailyMenu.provider')->get();
            $props['myOrdersToday'] = $myOrdersToday->values();
            $props['openSessions'] = $openStatuses->values();
            
            // Get authorizations for THIS USER today
            $userAuthorizations = \App\Models\SessionAuthorization::where('user_id', $user->id)
                ->whereIn('provider_daily_status_id', $openStatuses->pluck('id'))
                ->pluck('provider_daily_status_id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            $availableSessions = $openStatuses->filter(function($session) use ($user, $userAuthorizations) {
                $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                $isAreaSelected = in_array((int)$user->area_id, array_map('intval', $areas ?: []));
                
                // NEW: Must also be authorized by Area Manager
                return $isAreaSelected && in_array((int)$session->id, $userAuthorizations);
            })->values();

            $props['pendingAuthorizations'] = $openStatuses->filter(function($session) use ($user, $userAuthorizations) {
                $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
                $isAreaSelected = in_array((int)$user->area_id, array_map('intval', $areas ?: []));
                return $isAreaSelected && !in_array((int)$session->id, $userAuthorizations);
            })->values();

            $availableMenus = [];
            foreach ($availableSessions as $session) {
                $menus = DailyMenu::where('provider_id', $session->provider_id)
                    ->where('available_on', $today)
                    ->where('status', 'published')
                    ->with('provider')
                    ->get();
                foreach ($menus as $menu) {
                    $menu->meal_type = $session->meal_type;
                    $menu->already_ordered = $myOrdersToday->where('meal_type', $session->meal_type)->count() > 0;
                    $availableMenus[] = $menu;
                }
            }
            $props['availableMenus'] = $availableMenus;
            $props['orderHistory'] = Order::where('user_id', $user->id)->with('dailyMenu.provider')->latest()->take(5)->get();
        }

        return Inertia::render('Dashboard', $props);
    }

    public function showJustificationPage(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $props = ['userRole' => $user->role];

        if ($user->role === 'area_manager') {
            $historicalSessions = ProviderDailyStatus::where('date', '<=', $today)
                ->where('status', 'closed')
                ->where(function($q) use ($user) {
                    $q->whereJsonContains('selected_area_ids', (int)$user->area_id);
                })
                ->with('provider')
                ->latest('date')
                ->take(20)
                ->get();

            $props['sessions'] = $historicalSessions->map(function($session) use ($user) {
                $orders = Order::where('meal_type', $session->meal_type)
                    ->whereHas('dailyMenu', function($q) use ($session) {
                        $q->where('provider_id', $session->provider_id)
                          ->where('available_on', $session->date);
                    })
                    ->whereHas('user', function($q) use ($user) {
                        $q->where('area_id', $user->area_id);
                    })
                    ->with(['user', 'dailyMenu'])
                    ->get();

                return [
                    'id' => $session->id,
                    'provider_id' => $session->provider_id, // Added
                    'date' => $session->date,
                    'meal_type' => $session->meal_type,
                    'provider_name' => $session->provider->name,
                    'justified_count' => $orders->whereNotNull('activity_performed')->where('activity_performed', '!=', '')->count(),
                    'total_orders' => $orders->count(),
                    'orders' => $orders->map(fn($o) => [
                        'id' => $o->id,
                        'user_name' => $o->user->name,
                        'platillo' => $o->dailyMenu->name,
                        'activity_performed' => $o->activity_performed,
                    ]),
                ];
            })->values();
        } elseif ($user->role === 'diner') {
            $orders = Order::where('user_id', $user->id)
                ->with(['dailyMenu.provider'])
                ->latest()
                ->take(20)
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
        if ($user->role !== 'area_manager') abort(403);

        $orders = Order::whereHas('user', function($q) use ($user) {
                $q->where('area_id', $user->area_id);
            })
            ->with(['user', 'dailyMenu.provider'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Orders/AreaHistory', [
            'orders' => $orders,
            'area' => $user->area
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

        // NEW: Check if there are published dishes for this provider on this date
        $dishesCount = DailyMenu::where('provider_id', $provider->id)
                                ->where('available_on', $date)
                                ->where('status', 'published')
                                ->count();
        
        if ($dishesCount === 0) {
            return back()->withErrors([
                'error' => "No puedes activar el servicio para '{$provider->name}' porque no tiene platillos publicados para el día {$date}. Agrega y publica platillos primero."
            ]);
        }

        $existingOtherSessions = ProviderDailyStatus::where('date', $date)
            ->where('meal_type', $mealType)
            ->where('provider_id', '!=', $provider->id)
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
                    
                    if ($request->conflict_resolution === 'restart') {
                        Order::whereIn('user_id', function($query) use ($conflicts) {
                                $query->select('id')->from('users')->whereIn('area_id', $conflicts);
                            })
                            ->where('meal_type', $mealType)
                            ->whereHas('dailyMenu', function($q) use ($otherSession) {
                                $q->where('provider_id', $otherSession->provider_id)
                                  ->where('available_on', $otherSession->date);
                            })
                            ->delete();
                    }
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

        Order::where('meal_type', $session->meal_type)
            ->whereHas('dailyMenu', function($q) use ($session) {
                $q->where('provider_id', $session->provider_id)
                  ->where('available_on', $session->date);
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
            \App\Models\SessionAuthorization::where('provider_daily_status_id', $session->id)
                ->whereHas('user', function($q) use ($removedAreaIds) {
                    $q->whereIn('area_id', $removedAreaIds);
                })->delete();

            // 2. Remove orders for users in removed areas for this specific session context
            \App\Models\Order::where('meal_type', $session->meal_type)
                ->whereHas('dailyMenu', function($q) use ($session) {
                    $q->where('provider_id', $session->provider_id)
                      ->where('available_on', $session->date);
                })
                ->whereHas('user', function($q) use ($removedAreaIds) {
                    $q->whereIn('area_id', $removedAreaIds);
                })->delete();
        }

        $session->update(['selected_area_ids' => $newAreaIds]);
        
        return redirect()->route('dashboard')->with('success', 'Áreas actualizadas y datos de pedidos/autorizaciones limpiados.');
    }

    public function showOrderSummary(Provider $provider, string $date, Request $request)
    {
        $mealType = $request->query('meal_type', 'Comida');
        $orders = Order::where('meal_type', $mealType)
            ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id)->where('available_on', $date))
            ->with(['user.area', 'dailyMenu'])->get();

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
        ]);
    }

    public function showSendOrderView(Provider $provider, string $date, Request $request)
    {
        $mealType = $request->query('meal_type', 'Comida');

        $orders = Order::where('meal_type', $mealType)
            ->whereHas('dailyMenu', function ($query) use ($provider, $date) {
                $query->where('provider_id', $provider->id)
                      ->where('available_on', $date);
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

    public function generatePdfReport(Provider $provider, string $date, Request $request)
    {
        try {
            $mealType = $request->query('meal_type', 'Comida');
            $areaId = $request->query('area_id');
            $sortBy = $request->query('sort', 'area'); 
            $viewMode = $request->query('view_mode', 'detailed'); 
            $format = $request->query('format', 'pdf');

            $query = Order::where('meal_type', $mealType)
                ->whereHas('dailyMenu', fn($q) => $q->where('provider_id', $provider->id)->where('available_on', $date))
                ->with(['user.area', 'dailyMenu']);

            if ($areaId) $query->whereHas('user', fn($q) => $q->where('area_id', $areaId));
            $orders = $query->get();

            if ($sortBy === 'platillo') {
                $summary = $orders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => [
                    'group_name' => $pName,
                    'total_count' => $pOrders->count(),
                    'platillos' => [['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()]],
                    'individual_orders' => $pOrders->sortBy('user.name')->map(fn($o) => [
                        'user_name' => $o->user->name, 
                        'avatar_url' => $o->user->avatar ? public_path('storage/' . $o->user->avatar) : null,
                        'area_name' => $o->user->area->name, 
                        'platillo_name' => $o->dailyMenu->name, 
                        'preferences' => $o->preferences, 
                        'activity_performed' => $o->activity_performed
                    ]),
                ])->sortKeys();
            } elseif ($sortBy === 'name') {
                $summary = collect(['Listado General' => [
                    'group_name' => 'Listado Alfabético General',
                    'total_count' => $orders->count(),
                    'platillos' => $orders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => ['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()])->values()->sortBy('platillo_name')->values(),
                    'individual_orders' => $orders->sortBy('user.name')->map(fn($o) => [
                        'user_name' => $o->user->name, 
                        'avatar_url' => $o->user->avatar ? public_path('storage/' . $o->user->avatar) : null,
                        'area_name' => $o->user->area->name, 
                        'platillo_name' => $o->dailyMenu->name, 
                        'preferences' => $o->preferences, 
                        'activity_performed' => $o->activity_performed
                    ]),
                ]]);
            } else {
                $summary = $orders->groupBy('user.area.name')->map(fn($aOrders, $aName) => [
                    'group_name' => $aName,
                    'total_count' => $aOrders->count(),
                    'platillos' => $aOrders->groupBy('dailyMenu.name')->map(fn($pOrders, $pName) => ['platillo_name' => $pName, 'total_count' => $pOrders->count(), 'observations' => $pOrders->pluck('preferences')->filter()->values()])->values()->sortBy('platillo_name')->values(),
                    'individual_orders' => $aOrders->sortBy('user.name')->map(fn($o) => [
                        'user_name' => $o->user->name, 
                        'avatar_url' => $o->user->avatar ? public_path('storage/' . $o->user->avatar) : null,
                        'area_name' => $o->user->area->name, 
                        'platillo_name' => $o->dailyMenu->name, 
                        'preferences' => $o->preferences, 
                        'activity_performed' => $o->activity_performed
                    ]),
                ])->sortKeys();
            }

            $data = [
                'provider' => $provider, 
                'date' => $date, 
                'mealType' => $mealType, 
                'ordersSummary' => $summary->values(), 
                'sortBy' => $sortBy, 
                'viewMode' => $viewMode, 
                'isSingleArea' => !empty($areaId),
                'isPdf' => ($format === 'pdf')
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
}
