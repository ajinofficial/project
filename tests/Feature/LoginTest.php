<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_field_validation_errors(): void
    {
        $response = $this
            ->from(route('login'))
            ->post(route('login.store'), []);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['tenant_id', 'email', 'password']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Business name is required.')
            ->assertSee('The email field is required.')
            ->assertSee('The password field is required.')
            ->assertSee('novalidate', false)
            ->assertSee('is required.', false)
            ->assertDontSee('Check your login');
    }

    public function test_ajax_login_returns_validation_errors_without_redirect(): void
    {
        $response = $this->postJson(route('login.store'), []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['tenant_id', 'email', 'password']);
    }

    public function test_ajax_login_returns_redirect_url_after_success(): void
    {
        $user = $this->tenantOwner();

        $response = $this->postJson(route('login.store'), [
            'tenant_id' => $user->tenant_id,
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('redirect', route('vendor.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_client_ajax_login_returns_dashboard_redirect_url_after_success(): void
    {
        $user = $this->tenantOwner(Tenant::TYPE_CLIENT, 'client-owner@example.com');

        $response = $this->postJson(route('login.store'), [
            'tenant_id' => $user->tenant_id,
            'email' => $user->email,
            'password' => 'Password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('redirect', route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_ajax_login_returns_email_error_for_unknown_email(): void
    {
        $user = $this->tenantOwner();

        $response = $this->postJson(route('login.store'), [
            'tenant_id' => $user->tenant_id,
            'email' => 'missing@example.com',
            'password' => 'Password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonMissingValidationErrors(['password']);

        $this->assertGuest();
    }

    public function test_ajax_login_returns_password_error_for_wrong_password(): void
    {
        $user = $this->tenantOwner();

        $response = $this->postJson(route('login.store'), [
            'tenant_id' => $user->tenant_id,
            'email' => $user->email,
            'password' => 'WrongPassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonMissingValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_page_uses_business_search_input(): void
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Alpha Market',
            'owner_name' => 'Alpha Owner',
            'mobile' => '+91 90000 00001',
            'email' => 'alpha@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Alpha road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Beta Pharmacy',
            'owner_name' => 'Beta Owner',
            'mobile' => '+91 90000 00002',
            'email' => 'beta@example.com',
            'business_category' => Tenant::CATEGORY_PHARMACY,
            'store_address' => 'Beta road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Search business name')
            ->assertSee('name="tenant_id"', false)
            ->assertSee('data-business-search', false)
            ->assertSee('data-business-list', false)
            ->assertDontSee('Alpha Market')
            ->assertDontSee('Beta Pharmacy')
            ->assertDontSee('alpha@example.com');
    }

    public function test_business_search_returns_matching_businesses(): void
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Alpha Market',
            'owner_name' => 'Alpha Owner',
            'mobile' => '+91 90000 00001',
            'email' => 'alpha@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Alpha road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Beta Pharmacy',
            'owner_name' => 'Beta Owner',
            'mobile' => '+91 90000 00002',
            'email' => 'beta@example.com',
            'business_category' => Tenant::CATEGORY_PHARMACY,
            'store_address' => 'Beta road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $response = $this->getJson(route('login.businesses', ['q' => 'alpha']));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.business_name', 'Alpha Market')
            ->assertJsonMissing(['email' => 'alpha@example.com'])
            ->assertJsonMissing(['business_name' => 'Beta Pharmacy']);
    }

    private function tenantOwner(int $tenantType = Tenant::TYPE_VENDOR, string $email = 'owner@example.com'): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => $tenantType,
            'business_name' => 'Demo Store',
            'owner_name' => 'Owner User',
            'mobile' => '+91 98765 43210',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner User',
            'email' => $email,
            'company_name' => 'Demo Store',
            'phone' => '9876543210',
            'country_code' => '+91',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);
    }
}
