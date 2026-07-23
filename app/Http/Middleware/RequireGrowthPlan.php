<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireGrowthPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->tenant;

        abort_unless(
            $tenant
                && (int) $tenant->tenant_type === Tenant::TYPE_CLIENT
                && $tenant->plan?->name === 'growth',
            403
        );

        return $next($request);
    }
}
