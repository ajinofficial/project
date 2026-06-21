<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'plans' => Plan::orderBy('monthly_price')->get(),
            'businessCategories' => Tenant::BUSINESS_CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gst_number' => ['nullable', 'string', 'max:30'],
            'business_category' => ['required', 'integer', Rule::in(array_keys(Tenant::BUSINESS_CATEGORIES))],
            'store_address' => ['required', 'string', 'max:1000'],
            'plan' => ['required', 'integer', 'exists:plans,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $plan = Plan::findOrFail($data['plan']);

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => $data['business_name'],
            'owner_name' => $data['owner_name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'],
            'gst_number' => $data['gst_number'] ?? null,
            'business_category' => $data['business_category'],
            'store_address' => $data['store_address'],
            'role_permissions' => RolePermission::defaults(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['owner_name'],
            'email' => $data['email'],
            'company_name' => $data['business_name'],
            'store_url' => Str::slug($data['business_name']).'-'.Str::lower(Str::random(5)),
            'phone' => $data['mobile'],
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => $data['password'],
        ]);

        Auth::login($user);

        return redirect()->route('setup.index')->with('status', 'Business workspace created. Complete store setup.');
    }
}
