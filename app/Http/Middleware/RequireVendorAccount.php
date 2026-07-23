<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVendorAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            (int) $request->user()?->tenant?->tenant_type === Tenant::TYPE_VENDOR,
            403
        );

        return $next($request);
    }
}
