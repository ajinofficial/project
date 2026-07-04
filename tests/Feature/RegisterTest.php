<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_business_owner_can_register_workspace(): void
    {
        Carbon::setTestNow('2026-07-04 10:00:00');
        $plan = $this->starterPlan();

        $response = $this->post(route('register.store'), $this->validRegistrationData([
            'plan' => $plan->id,
        ]));

        $response->assertRedirect(route('setup.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', [
            'business_name' => 'Mobile World',
            'email' => 'owner@example.com',
            'mobile' => '+91 9876543210',
            'gst_number' => '22AAAAA0000A1Z5',
        ]);
        $this->assertSame('2031-07-04', Tenant::where('email', 'owner@example.com')->firstOrFail()->domain_expired_date->toDateString());
        $this->assertDatabaseHas('users', [
            'name' => 'Ajay Kumar',
            'email' => 'owner@example.com',
            'company_name' => 'Mobile World',
            'country_code' => '+91',
            'phone' => '9876543210',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
        ]);
    }

    public function test_business_owner_can_register_with_free_trial_plan(): void
    {
        Carbon::setTestNow('2026-07-04 10:00:00');
        $plan = Plan::findOrFail(4);

        $response = $this->post(route('register.store'), $this->validRegistrationData([
            'plan' => $plan->id,
        ]));

        $response->assertRedirect(route('setup.index'));
        $this->assertAuthenticated();
        $this->assertSame('free_trial', $plan->name);
        $this->assertSame(1, $plan->user_limit);
        $this->assertDatabaseHas('tenants', [
            'email' => 'owner@example.com',
            'plan_id' => 4,
        ]);
        $this->assertSame('2026-08-03', Tenant::where('email', 'owner@example.com')->firstOrFail()->domain_expired_date->toDateString());
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'plan' => 4,
            'role' => User::ROLE_OWNER,
        ]);
    }

    public function test_registration_rejects_invalid_gst_and_weak_password(): void
    {
        $plan = $this->starterPlan();

        $response = $this->from(route('register'))->post(route('register.store'), $this->validRegistrationData([
            'plan' => $plan->id,
            'gst_number' => 'bad-gst',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['gst_number', 'password']);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_ajax_registration_returns_validation_errors_without_redirect(): void
    {
        $response = $this->postJson(route('register.store'), [
            'business_name' => '',
            'owner_name' => '',
            'country_code' => 'bad',
            'mobile' => 'bad',
            'email' => 'not-email',
            'business_category' => '',
            'store_address' => '',
            'plan' => 999,
            'password' => 'weak',
            'password_confirmation' => 'different',
            'terms_accepted' => '0',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'business_name',
            'owner_name',
            'country_code',
            'mobile',
            'email',
            'business_category',
            'store_address',
            'plan',
            'password',
            'terms_accepted',
        ]);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_ajax_registration_returns_setup_redirect_after_success(): void
    {
        $plan = $this->starterPlan();

        $response = $this->postJson(route('register.store'), $this->validRegistrationData([
            'plan' => $plan->id,
        ]));

        $response->assertOk();
        $response->assertJson(['redirect' => route('setup.index')]);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'plan' => $plan->id,
        ]);
    }

    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Mobile World',
            'owner_name' => 'Ajay Kumar',
            'country_code' => '+91',
            'mobile' => '9876543210',
            'email' => 'OWNER@EXAMPLE.COM',
            'gst_number' => '22aaaaa0000a1z5',
            'business_category' => Tenant::CATEGORY_MOBILE,
            'store_address' => 'Shop 12, MG Road, Bengaluru',
            'plan' => 1,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'terms_accepted' => '1',
        ], $overrides);
    }

    private function starterPlan(): Plan
    {
        return Plan::where('name', 'starter')->firstOrFail();
    }
}
