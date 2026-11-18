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
            'business_name' => trim((string) $request->input('business_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'country_code' => $request->input('country_code') ?: '+91',
            'mobile' => preg_replace('/\D+/', '', (string) $request->input('mobile')),
            'gst_number' => $gstNumber === '' ? null : Str::upper($gstNumber),
        ]);

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255', Rule::unique('tenants', 'business_name')],
            'owner_name' => ['required', 'string', 'max:255'],
            'country_code' => ['required', 'string', Rule::in(array_keys($this->countryCodes()))],
            'mobile' => ['required', 'digits_between:6,15'],
            'email' => ['required', new \App\Rules\SafeEmail, 'max:255', 'unique:users,email'],
            'gst_number' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'business_category' => ['required', 'integer', Rule::in(array_keys(Tenant::BUSINESS_CATEGORIES))],
            'store_address' => ['required', 'string', 'max:1000'],
            'plan' => ['required', 'integer', 'exists:plans,id'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'terms_accepted' => ['accepted'],
        ], [
            'mobile.digits_between' => __('validation.mobile_number_must_be_6_to_15_digits_a58447'),
            'business_name.unique' => __('auth.this_business_name_is_already_registered_8792a4'),
            'email.unique' => __('auth.this_email_address_is_already_registered_8aba0f'),
            'gst_number.regex' => __('common.enter_a_valid_15_character_gst_number_2f3e92'),
            'password.letters' => __('auth.password_must_include_at_least_one_letter_2df943'),
            'password.numbers' => __('auth.password_must_include_at_least_one_number_17fe5d'),
            'terms_accepted.accepted' => __('common.confirm_that_you_are_authorized_to_create_this_workspace_9af9a3'),
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
        $request->session()->regenerate();
        \App\Support\LocalePreference::afterLogin($request);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('setup.index')]);
        }

        return redirect()->route('setup.index')->with('status', __('auth.business_workspace_created_complete_store_setup_6b0d01'));
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'field' => ['required', Rule::in(['business_name', 'email'])],
            'value' => ['required', 'string', 'max:255'],
        ]);

        $value = trim($data['value']);
        $available = $data['field'] === 'email'
            ? filter_var($value, FILTER_VALIDATE_EMAIL)
                && ! User::where('email', Str::lower($value))->exists()
            : ! Tenant::where('business_name', $value)->exists();

        return response()->json([
            'available' => (bool) $available,
            'message' => $available
                ? ($data['field'] === 'email' ? __('auth.email_address_is_available_5bb409') : __('auth.business_name_is_available_ec5151'))
                : ($data['field'] === 'email' ? __('auth.this_email_address_is_already_registered_8aba0f') : __('auth.this_business_name_is_already_registered_8792a4')),
        ]);
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
            '+91' => __('common.india_91_688380'),
            '+1' => __('common.usa_canada_1_4e3f65'),
            '+44' => __('common.united_kingdom_44_60072d'),
            '+61' => __('common.australia_61_b92110'),
            '+971' => __('common.united_arab_emirates_971_02ccf6'),
        ];
    }
}
