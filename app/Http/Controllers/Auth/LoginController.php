<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'businesses' => Tenant::query()
                ->orderBy('business_name')
                ->limit(50)
                ->get(['id', 'business_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'tenant_id.required' => 'Business name is required.',
            'tenant_id.exists' => 'Select a valid business name.',
        ]);

        $authCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($authCredentials, $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The provided credentials do not match our records.',
                    'errors' => [
                        'email' => ['The provided credentials do not match our records.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        if ((int) $request->user()->tenant_id !== (int) $credentials['tenant_id']) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This account does not belong to the selected business.',
                    'errors' => [
                        'tenant_id' => ['This account does not belong to the selected business.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['tenant_id' => 'This account does not belong to the selected business.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $redirect = route(RolePermission::firstAccessibleRoute($request->user()));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
