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
            'email' => ['required', new \App\Rules\SafeEmail],
            'password' => ['required', 'string'],
        ], [
            'tenant_id.required' => __('auth.business_name_is_required_0aef89'),
            'tenant_id.exists' => __('validation.select_a_valid_business_name_467454'),
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('validation.we_could_not_find_an_account_with_this_email_7536fa'),
                    'errors' => [
                        'email' => [__('validation.we_could_not_find_an_account_with_this_email_7536fa')],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['email' => __('validation.we_could_not_find_an_account_with_this_email_7536fa')])
                ->onlyInput('email', 'tenant_id');
        }

        if ((int) $user->tenant_id !== (int) $credentials['tenant_id']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('common.this_account_does_not_belong_to_the_selected_business_df11a1'),
                    'errors' => [
                        'tenant_id' => [__('common.this_account_does_not_belong_to_the_selected_business_df11a1')],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['tenant_id' => __('common.this_account_does_not_belong_to_the_selected_business_df11a1')])
                ->onlyInput('email', 'tenant_id');
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('auth.the_password_is_incorrect_0e7258'),
                    'errors' => [
                        'password' => [__('auth.the_password_is_incorrect_0e7258')],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['password' => __('auth.the_password_is_incorrect_0e7258')])
                ->onlyInput('email', 'tenant_id');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        \App\Support\LocalePreference::afterLogin($request);

        $redirect = route(RolePermission::firstAccessibleRoute($request->user()));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $redirect]);
        }

        return redirect($redirect);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $locale = app()->getLocale();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        \App\Support\LocalePreference::remember($request, $locale);

        return redirect()->route('login');
    }
}
