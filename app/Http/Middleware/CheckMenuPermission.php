<?php

namespace App\Http\Middleware;

use App\Support\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    public function handle(Request $request, Closure $next, string $menu): Response
    {
        abort_unless(RolePermission::canAccess($request->user(), $menu), 403);

        return $next($request);
    }
}
