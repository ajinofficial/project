<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_owner_can_register_workspace(): void
    {
        $plan = $this->starterPlan();

        $response = $this->post(route('register.store'), $this->validRegistrationData([
            'plan' => $plan->id,
        ]));

        $response->assertRedirect(route('setup.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('tenants', [
            'business_name' => 'Mobile World',
            'email' => 'owner@example.com',
            'gst_number' => '22AAAAA0000A1Z5',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Ajay Kumar',
            'email' => 'owner@example.com',
            'company_name' => 'Mobile World',
            'plan' => $plan->id,
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

    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'business_name' => 'Mobile World',
            'owner_name' => 'Ajay Kumar',
            'mobile' => '+91 98765 43210',
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
