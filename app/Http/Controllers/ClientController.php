<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless((int) $request->user()->tenant?->tenant_type === Tenant::TYPE_VENDOR, 403);

        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $baseQuery = Tenant::query()
            ->with('plan')
            ->withCount('users')
            ->where('tenant_type', Tenant::TYPE_CLIENT);

        $stats = [
            'clients' => (clone $baseQuery)->count(),
            'users' => User::query()
                ->whereHas('tenant', fn ($query) => $query->where('tenant_type', Tenant::TYPE_CLIENT))
                ->count(),
            'starter' => (clone $baseQuery)->whereHas('plan', fn ($query) => $query->where('name', 'starter'))->count(),
            'premium' => (clone $baseQuery)->whereHas('plan', fn ($query) => $query->where('name', 'premium'))->count(),
        ];

        $hasActiveFilters = $request->filled('search') || $request->filled('plan_id');

        $clients = (clone $baseQuery)
            ->when($request->filled('plan_id'), fn ($query) => $query->where('plan_id', (int) $request->input('plan_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('business_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        return view('clients.index', [
            'clients' => $clients,
            'plans' => Plan::orderBy('monthly_price')->get(),
            'stats' => $stats,
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }
}
