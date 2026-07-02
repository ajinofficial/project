<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityNotifier;
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

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'country_code' => $request->input('country_code') ?: '+91',
        ]);

        $tenant = $request->user()->tenant()->with('plan')->firstOrFail();
        $userLimit = $tenant->plan?->user_limit;
        $userCount = User::where('tenant_id', $tenant->id)->count();

        if (! is_null($userLimit) && $userCount >= $userLimit) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['limit' => 'Your current plan allows '.$userLimit.' users. Upgrade the plan or remove an account before creating another user.']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id),
            ],
            'country_code' => ['required', 'string', Rule::in(array_keys($this->countryCodes()))],
            'phone' => ['nullable', 'digits_between:6,15'],
            'role' => ['required', 'integer', Rule::in(array_keys($this->creatableRoles()))],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ], [
            'phone.digits_between' => 'Phone number must be 6 to 15 digits.',
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
            'User account created',
            $request->user()->name.' created '.$createdUser->name.' as '.$createdUser->role_label.'.'
        );

        return redirect()->route('users.index')->with('status', 'User account created.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);

        if ((int) $user->role === User::ROLE_OWNER) {
            return back()->withErrors(['delete' => 'Owner accounts cannot be deleted.']);
        }

        $deletedName = $user->name;
        $deletedRole = $user->role_label;

        $user->delete();

        ActivityNotifier::notify(
            $request->user()->tenant_id,
            'user_deleted',
            'User account deleted',
            $request->user()->name.' deleted '.$deletedName.' from '.$deletedRole.' access.'
        );

        return redirect()->route('users.index')->with('status', 'User account deleted.');
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
            '+91' => 'India (+91)',
            '+1' => 'USA/Canada (+1)',
            '+44' => 'United Kingdom (+44)',
            '+61' => 'Australia (+61)',
            '+971' => 'United Arab Emirates (+971)',
        ];
    }
}
