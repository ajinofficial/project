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
        $response->assertJsonPath('redirect', route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_lists_businesses_in_dropdown(): void
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
            ->assertSee('Select business')
            ->assertSee('name="tenant_id"', false)
            ->assertSee('Alpha Market')
            ->assertSee('Beta Pharmacy')
            ->assertDontSee('data-business-search', false)
            ->assertDontSee('data-business-option', false)
            ->assertDontSee('data-business-list', false)
            ->assertDontSee('alpha@example.com');
    }

    private function tenantOwner(): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Demo Store',
            'owner_name' => 'Owner User',
            'mobile' => '+91 98765 43210',
            'email' => 'owner@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'company_name' => 'Demo Store',
            'phone' => '9876543210',
            'country_code' => '+91',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);
    }
}
