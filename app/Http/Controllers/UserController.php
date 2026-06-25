<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $tenant = $request->user()->tenant()->with('plan')->firstOrFail();
        $users = User::where('tenant_id', $tenant->id)
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10);

        $userLimit = $tenant->plan?->user_limit;
        $userCount = User::where('tenant_id', $tenant->id)->count();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->creatableRoles(),
            'tenant' => $tenant,
            'userLimit' => $userLimit,
            'userCount' => $userCount,
            'remainingUsers' => is_null($userLimit) ? null : max(0, $userLimit - $userCount),
            'canCreateUser' => is_null($userLimit) || $userCount < $userLimit,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
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
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', 'integer', Rule::in(array_keys($this->creatableRoles()))],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'company_name' => $tenant->business_name,
            'store_url' => null,
            'phone' => $data['phone'] ?? null,
            'plan' => $tenant->plan_id ?: $request->user()->plan,
            'role' => $data['role'],
            'password' => $data['password'],
        ]);

        return redirect()->route('users.index')->with('status', 'User account created.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);

        if ((int) $user->role === User::ROLE_OWNER) {
            return back()->withErrors(['delete' => 'Owner accounts cannot be deleted.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User account deleted.');
    }

    private function creatableRoles(): array
    {
        return collect(User::ROLES)
            ->except([User::ROLE_OWNER])
            ->all();
    }
}
