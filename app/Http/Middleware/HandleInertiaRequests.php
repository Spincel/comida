<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $orderStatus = null;

        if ($user && $user->area_id) {
            $today = now()->toDateString();
            
            // 1. Get open sessions for user's area
            $openSessions = \App\Models\ProviderDailyStatus::where('date', $today)
                ->where('status', 'open')
                ->whereJsonContains('selected_area_ids', (int)$user->area_id)
                ->get();

            // NEW: Get closed sessions for today to allow justification
            $closedSessionsToday = \App\Models\ProviderDailyStatus::where('date', $today)
                ->where('status', 'closed')
                ->whereJsonContains('selected_area_ids', (int)$user->area_id)
                ->exists();

            if ($openSessions->isNotEmpty()) {
                $userOrders = \App\Models\Order::where('user_id', $user->id)
                    ->whereHas('dailyMenu', fn($q) => $q->where('available_on', $today))
                    ->get();

                // Logic to determine priority color
                $statusColors = [];
                foreach ($openSessions as $session) {
                    $order = $userOrders->firstWhere('meal_type', $session->meal_type);
                    
                    if (!$order) {
                        $statusColors[] = 'red'; // Missing order for an open session
                    } elseif ($order->status === 'submitted_by_manager' || $order->status === 'delivered') {
                        $statusColors[] = 'green'; // Fully processed
                    } else {
                        $statusColors[] = 'amber'; // Ordered but pending manager
                    }
                }

                // Priority: Red > Amber > Green
                if (in_array('red', $statusColors)) $orderStatus = 'red';
                elseif (in_array('amber', $statusColors)) $orderStatus = 'amber';
                else $orderStatus = 'green';
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'orderStatus' => $orderStatus,
                'isAnySessionOpen' => isset($openSessions) ? $openSessions->isNotEmpty() : false,
                'isAnySessionClosedToday' => $closedSessionsToday ?? false,
            ],
            'system' => [
                'settings' => \App\Models\SystemSetting::all()->pluck('value', 'key'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
