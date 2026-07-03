<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $clients = Tenant::query()
            ->where('tenant_type', Tenant::TYPE_CLIENT);

        $stats = [
            'clients' => (clone $clients)->count(),
            'users' => User::query()
                ->whereHas('tenant', fn ($query) => $query->where('tenant_type', Tenant::TYPE_CLIENT))
                ->count(),
            'plans' => Plan::count(),
            'new_clients' => (clone $clients)->whereDate('created_at', '>=', now()->subDays(30)->toDateString())->count(),
        ];

        $planBreakdown = Plan::query()
            ->withCount(['tenants as clients_count' => fn ($query) => $query->where('tenant_type', Tenant::TYPE_CLIENT)])
            ->orderBy('monthly_price')
            ->get();

        $recentClients = (clone $clients)
            ->with('plan')
            ->withCount('users')
            ->latest()
            ->take(8)
            ->get();

        return view('vendor-dashboard', [
            'stats' => $stats,
            'planBreakdown' => $planBreakdown,
            'recentClients' => $recentClients,
        ]);
    }
}
