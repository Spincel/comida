<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\ProviderDailyStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AcquisitionsController extends Controller
{
    /**
     * Display a listing of the resource (Acquisitions Dashboard).
     */
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $startOfWeek = Carbon::today()->startOfWeek()->toDateString();
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();

        $providers = Provider::all()->map(function ($provider) use ($today, $startOfWeek, $startOfMonth) {
            $provider->dailyStatus = ProviderDailyStatus::where('provider_id', $provider->id)
                                                      ->where('date', $today)
                                                      ->first();
            // Dummy stats for now
            $provider->weekly_orders = rand(0, 5); // Example: how many days this week they had orders
            $provider->monthly_orders_count = rand(10, 50); // Example: total orders this month

            return $provider;
        });

        return Inertia::render('Admin/AcquisitionsDashboard', [
            'providers' => $providers,
        ]);
    }
}
