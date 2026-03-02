<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\DailyMenu;
use App\Models\ProviderDailyStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Store a new order from a diner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'daily_menu_id' => 'required|exists:daily_menus,id',
            'meal_type' => 'required|string', // Added
            'preferences' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // Security check: Is this menu open for this user's area?
        $menu = DailyMenu::findOrFail($validated['daily_menu_id']);
        $status = ProviderDailyStatus::where('provider_id', $menu->provider_id)
                                     ->where('date', $today)
                                     ->where('meal_type', $validated['meal_type']) // Specific check
                                     ->where('status', 'open')
                                     ->whereJsonContains('selected_area_ids', (int)$user->area_id)
                                     ->first();

        if (!$status) {
            return back()->withErrors(['error' => 'Este menú no está disponible para tu área o ya ha sido cerrado.']);
        }

        // NEW: Check if user is authorized by Area Manager for this session
        $isAuthorized = \App\Models\SessionAuthorization::where('provider_daily_status_id', $status->id)
                                                        ->where('user_id', $user->id)
                                                        ->exists();
        
        if (!$isAuthorized) {
            return back()->withErrors(['error' => 'No estás autorizado por tu gerente para realizar pedidos en esta sesión.']);
        }

        // Check if already has an order for THIS SPECIFIC meal type today
        $existingOrder = Order::where('user_id', $user->id)
                              ->where('meal_type', $validated['meal_type']) // Check specifically for meal type
                              ->whereHas('dailyMenu', function ($query) use ($today) {
                                  $query->where('available_on', $today);
                              })
                              ->first();

        if ($existingOrder) {
            return back()->withErrors(['error' => "Ya has realizado un pedido de {$validated['meal_type']} para el día de hoy."]);
        }

        Order::create([
            'user_id' => $user->id,
            'daily_menu_id' => $validated['daily_menu_id'],
            'meal_type' => $validated['meal_type'],
            'preferences' => $validated['preferences'],
            'status' => 'submitted_by_user',
        ]);

        return redirect()->route('dashboard')->with('success', '¡Tu pedido ha sido registrado con éxito!');
    }

    /**
     * Update an existing order.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'daily_menu_id' => 'required|exists:daily_menus,id',
            'meal_type' => 'required|string', // Added
            'preferences' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        if ($order->user_id !== $user->id) {
            abort(403);
        }

        $today = Carbon::today()->toDateString();
        
        // Security check: Is this menu open for this user's area?
        $menu = DailyMenu::findOrFail($validated['daily_menu_id']);
        $status = ProviderDailyStatus::where('provider_id', $menu->provider_id)
                                     ->where('date', $today)
                                     ->where('meal_type', $validated['meal_type'])
                                     ->where('status', 'open')
                                     ->whereJsonContains('selected_area_ids', (int)$user->area_id)
                                     ->first();

        if (!$status) {
            return back()->withErrors(['error' => 'No puedes editar tu pedido porque el menú para tu área está cerrado.']);
        }

        $order->update([
            'daily_menu_id' => $validated['daily_menu_id'],
            'meal_type' => $validated['meal_type'],
            'preferences' => $validated['preferences'],
            'status' => 'submitted_by_user', // Reset status so area manager must re-approve if they already did
        ]);

        return redirect()->route('dashboard')->with('success', '¡Tu pedido ha sido actualizado correctamente!');
    }

    /**
     * Area Manager authorizes specific diners for a session.
     */
    public function authorizeDiners(Request $request)
    {
        $validated = $request->validate([
            'provider_daily_status_id' => 'required|exists:provider_daily_statuses,id',
            'user_ids' => 'present|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $manager = $request->user();
        // Allow any manager role with an area
        if (!in_array($manager->role, ['area_manager', 'admin', 'acquisitions_manager']) || !$manager->area_id) {
            abort(403, 'No tienes permisos para autorizar comensales o no tienes un área asignada.');
        }

        $session = ProviderDailyStatus::findOrFail($validated['provider_daily_status_id']);
        
        // Security: Ensure the session is open for the manager's area
        $areas = is_array($session->selected_area_ids) ? $session->selected_area_ids : json_decode($session->selected_area_ids, true);
        if (!in_array((int)$manager->area_id, array_map('intval', $areas ?: []))) {
            abort(403, 'Esta sesión no está disponible para tu área.');
        }

        // Get only users belonging to the manager's area
        $validUserIds = \App\Models\User::where('area_id', $manager->area_id)
            ->whereIn('id', $validated['user_ids'])
            ->pluck('id')
            ->toArray();

        // Use transaction for safety
        \Illuminate\Support\Facades\DB::transaction(function () use ($session, $validUserIds, $manager) {
            // Remove existing authorizations for this area's users in this session
            \App\Models\SessionAuthorization::where('provider_daily_status_id', $session->id)
                ->whereIn('user_id', \App\Models\User::where('area_id', $manager->area_id)->pluck('id'))
                ->delete();

            // Insert new authorizations
            $authorizations = [];
            foreach ($validUserIds as $userId) {
                $authorizations[] = [
                    'provider_daily_status_id' => $session->id,
                    'user_id' => $userId,
                    'authorized_by_user_id' => $manager->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($authorizations)) {
                \App\Models\SessionAuthorization::insert($authorizations);
            }
        });

        return back()->with('success', 'Comensales autorizados correctamente.');
    }

    /**
     * Area Manager submits selected orders for their area.
     */
    public function submitAreaOrders(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|exists:orders,id',
            'meal_type' => 'required|string',
        ]);

        $user = $request->user();
        if (!in_array($user->role, ['area_manager', 'admin', 'acquisitions_manager']) || !$user->area_id) {
            abort(403, 'No tienes permisos para enviar pedidos de área.');
        }

        $updatedCount = Order::whereIn('id', $request->order_ids)
            ->where('status', 'submitted_by_user')
            ->update(['status' => 'submitted_by_manager']);

        return redirect()->route('dashboard')->with('success', "Se han enviado $updatedCount pedidos de {$request->meal_type} a Adquisiciones.");
    }

    /**
     * Save justifications/activities for a batch of orders.
     */
    public function saveJustifications(Request $request)
    {
        $request->validate([
            'justifications' => 'required|array',
            'justifications.*.id' => 'required|exists:orders,id',
            'justifications.*.activity_performed' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        if ($user->role !== 'area_manager') {
            abort(403);
        }

        foreach ($request->justifications as $item) {
            Order::where('id', $item['id'])->update([
                'activity_performed' => $item['activity_performed']
            ]);
        }

        return back()->with('success', 'Justificaciones guardadas correctamente.');
    }

    /**
     * Update activity justification.
     * Allowed for the owner of the order OR the Area Manager of that area.
     */
    public function updateOwnJustification(Request $request, Order $order)
    {
        $validated = $request->validate([
            'activity_performed' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        
        // Permission check:
        // 1. Is the owner?
        // 2. Is the area manager of the area where the order belongs?
        $isOwner = $order->user_id === $user->id;
        $isManagerOfArea = ($user->role === 'area_manager' && $order->user->area_id === $user->area_id);

        if (!$isOwner && !$isManagerOfArea) {
            abort(403, 'No tienes permiso para editar esta justificación.');
        }

        $order->update([
            'activity_performed' => $validated['activity_performed']
        ]);

        return back()->with('success', 'Actividad registrada correctamente.');
    }
}
