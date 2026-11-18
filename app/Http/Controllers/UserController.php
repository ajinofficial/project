<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $perPageOptions = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 10);

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 10;
        }

        $tenant = $request->user()->tenant()->with('plan')->firstOrFail();
        $baseQuery = User::where('tenant_id', $tenant->id);
        $hasActiveFilters = $request->filled('search') || $request->filled('role');

        $users = (clone $baseQuery)
            ->when($request->filled('role'), fn ($query) => $query->where('role', (int) $request->input('role')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = (string) $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends(array_merge($request->except('page'), ['per_page' => $perPage]));

        $userLimit = $tenant->plan?->user_limit;
        $userCount = (clone $baseQuery)->count();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->creatableRoles(),
            'filterRoles' => User::ROLES,
            'countryCodes' => $this->countryCodes(),
            'tenant' => $tenant,
            'userLimit' => $userLimit,
            'userCount' => $userCount,
            'remainingUsers' => is_null($userLimit) ? null : max(0, $userLimit - $userCount),
            'canCreateUser' => is_null($userLimit) || $userCount < $userLimit,
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
            'hasActiveFilters' => $hasActiveFilters,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'country_code' => $request->input('country_code') ?: '+91',
        ]);

        $tenant = $request->user()->tenant()->with('plan')->firstOrFail();
        $userLimit = $tenant->plan?->user_limit;
        $userCount = User::where('tenant_id', $tenant->id)->count();

        if (! is_null($userLimit) && $userCount >= $userLimit) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('settings.user_limit', ['count' => $userLimit]),
                    'errors' => ['limit' => [__('settings.user_limit', ['count' => $userLimit])]],
                ], 422);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['limit' => __('settings.user_limit', ['count' => $userLimit])]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                new \App\Rules\SafeEmail,
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id),
            ],
            'country_code' => ['required', 'string', Rule::in(array_keys($this->countryCodes()))],
            'phone' => ['nullable', 'digits_between:6,15'],
            'role' => ['required', 'integer', Rule::in(array_keys($this->creatableRoles()))],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ], [
            'phone.digits_between' => __('validation.phone_number_must_be_6_to_15_digits_5a2196'),
        ]);

        $createdUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'company_name' => $tenant->business_name,
            'store_url' => null,
            'country_code' => $data['country_code'],
            'phone' => $data['phone'] ?? null,
            'plan' => $tenant->plan_id ?: $request->user()->plan,
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        ActivityNotifier::notify(
            $tenant->id,
            'user_created',
            "User account created",
            $request->user()->name.' created '.$createdUser->name.' as '.$createdUser->role_label.'.',
            parameters: ['actor' => $request->user()->name, 'name' => $createdUser->name, 'role' => $createdUser->role_label]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('common.user_account_created_8b02fc'),
                'redirect' => route('users.index'),
            ], 201);
        }

        return redirect()->route('users.index')->with('status', __('common.user_account_created_8b02fc'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);

        if ((int) $user->role === User::ROLE_OWNER) {
            return back()->withErrors(['delete' => __('validation.owner_accounts_cannot_be_deleted_f16f81')]);
        }

        $deletedName = $user->name;
        $deletedRole = $user->role_label;

        $user->delete();

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'user_deleted',
            "User account deleted",
            $request->user()->name.' deleted '.$deletedName.' from '.$deletedRole.' access.',
            parameters: ['actor' => $request->user()->name, 'name' => $deletedName, 'role' => $deletedRole]
        );

        return redirect()->route('users.index')->with('status', __('common.user_account_deleted_5b5a5c'));
    }

    private function creatableRoles(): array
    {
        return collect(User::ROLES)
            ->except([User::ROLE_OWNER])
            ->all();
    }

    private function countryCodes(): array
    {
        return [
            '+91' => __('common.india_91_688380'),
            '+1' => __('common.usa_canada_1_4e3f65'),
            '+44' => __('common.united_kingdom_44_60072d'),
            '+61' => __('common.australia_61_b92110'),
            '+971' => __('common.united_arab_emirates_971_02ccf6'),
        ];
    }
}
