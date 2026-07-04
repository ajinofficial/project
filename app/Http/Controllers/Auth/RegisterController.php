<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'plans' => Plan::orderBy('monthly_price')->get(),
            'businessCategories' => Tenant::BUSINESS_CATEGORIES,
            'countryCodes' => $this->countryCodes(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $gstNumber = str_replace(' ', '', (string) $request->input('gst_number'));

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'country_code' => $request->input('country_code') ?: '+91',
            'mobile' => preg_replace('/\D+/', '', (string) $request->input('mobile')),
            'gst_number' => $gstNumber === '' ? null : Str::upper($gstNumber),
        ]);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', Rule::in(array_keys($this->countryCodes()))],
            'mobile' => ['required', 'digits_between:6,15'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'gst_number' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'business_category' => ['required', 'integer', Rule::in(array_keys(Tenant::BUSINESS_CATEGORIES))],
            'store_address' => ['required', 'string', 'max:1000'],
            'plan' => ['required', 'integer', 'exists:plans,id'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'terms_accepted' => ['accepted'],
        ], [
            'mobile.digits_between' => 'Mobile number must be 6 to 15 digits.',
            'gst_number.regex' => 'Enter a valid 15-character GST number.',
            'password.letters' => 'Password must include at least one letter.',
            'password.numbers' => 'Password must include at least one number.',
            'terms_accepted.accepted' => 'Confirm that you are authorized to create this workspace.',
        ]);

        $plan = Plan::findOrFail($data['plan']);

        $user = DB::transaction(function () use ($data, $plan) {
            $tenant = Tenant::create([
                'plan_id' => $plan->id,
                'tenant_type' => Tenant::TYPE_CLIENT,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'],
                'mobile' => $data['country_code'].' '.$data['mobile'],
                'email' => $data['email'],
                'gst_number' => $data['gst_number'] ?? null,
                'business_category' => $data['business_category'],
                'store_address' => $data['store_address'],
                'domain_expired_date' => $this->domainExpiredDateFor($plan),
                'role_permissions' => RolePermission::defaults(),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'company_name' => $data['business_name'],
                'store_url' => Str::slug($data['business_name']).'-'.Str::lower(Str::random(5)),
                'country_code' => $data['country_code'],
                'phone' => $data['mobile'],
                'plan' => $plan->id,
                'role' => User::ROLE_OWNER,
                'password' => $data['password'],
            ]);
        });

        Auth::login($user);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('setup.index')]);
        }

        return redirect()->route('setup.index')->with('status', 'Business workspace created. Complete store setup.');
    }

    private function domainExpiredDateFor(Plan $plan): string
    {
        return ((int) $plan->id === 4 || $plan->name === 'free_trial')
            ? now()->addDays(30)->toDateString()
            : now()->addYears(5)->toDateString();
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
