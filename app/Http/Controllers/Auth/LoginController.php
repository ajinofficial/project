<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        $oldTenant = old('tenant_id')
            ? Tenant::query()->find(old('tenant_id'), ['id', 'business_name'])
            : null;

        return view('auth.login', [
            'oldBusiness' => $oldTenant,
        ]);
    }

    public function businesses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $keyword = trim($validated['q'] ?? '');

        if ($keyword === '') {
            return response()->json(['data' => []]);
        }

        $businesses = Tenant::query()
            ->where('business_name', 'like', '%'.$keyword.'%')
            ->orderBy('business_name')
            ->limit(10)
            ->get(['id', 'business_name']);

        return response()->json(['data' => $businesses]);
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

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'We could not find an account with this email.',
                    'errors' => [
                        'email' => ['We could not find an account with this email.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['email' => 'We could not find an account with this email.'])
                ->onlyInput('email', 'tenant_id');
        }

        if ((int) $user->tenant_id !== (int) $credentials['tenant_id']) {
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
                ->onlyInput('email', 'tenant_id');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The password is incorrect.',
                    'errors' => [
                        'password' => ['The password is incorrect.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['password' => 'The password is incorrect.'])
                ->onlyInput('email', 'tenant_id');
        }

        Auth::login($user, $request->boolean('remember'));
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
