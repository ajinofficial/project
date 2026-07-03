<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_owner_can_open_clients_listing_from_sidebar(): void
    {
        [$owner] = $this->tenantOwner(Tenant::TYPE_VENDOR, 'Vendor HQ', 'vendor@example.com');
        $this->createClientTenant('Alpha Retail', 'alpha@example.com');

        $response = $this->actingAs($owner)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Clients');
        $response->assertSee('Vendor Console');
        $response->assertSee('Alpha Retail');
        $response->assertSee(route('clients.index'), false);
    }

    public function test_vendor_owner_lands_on_vendor_dashboard_not_client_dashboard(): void
    {
        [$owner] = $this->tenantOwner(Tenant::TYPE_VENDOR, 'Vendor HQ', 'vendor@example.com');
        $this->createClientTenant('Alpha Retail', 'alpha@example.com');

        $response = $this->actingAs($owner)->get(route('vendor.dashboard'));

        $response->assertOk();
        $response->assertSee('Vendor Dashboard');
        $response->assertSee('Alpha Retail');

        $this->actingAs($owner)->get(route('dashboard'))->assertForbidden();
    }

    public function test_client_owner_cannot_access_clients_listing_or_sidebar_menu(): void
    {
        [$owner] = $this->tenantOwner(Tenant::TYPE_CLIENT, 'Client Store', 'client-owner@example.com');

        $this->actingAs($owner)->get(route('clients.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('vendor.dashboard'))->assertForbidden();

        $dashboard = $this->actingAs($owner)->get(route('dashboard'));

        $dashboard->assertOk();
        $dashboard->assertDontSee(route('clients.index'), false);
        $dashboard->assertDontSee('Vendor Console');
    }

    public function test_clients_listing_can_be_filtered_by_search_and_plan(): void
    {
        [$owner] = $this->tenantOwner(Tenant::TYPE_VENDOR, 'Vendor HQ', 'vendor@example.com');
        $starter = Plan::where('name', 'starter')->firstOrFail();
        $premium = Plan::updateOrCreate([
            'name' => 'premium',
        ], [
            'monthly_price' => 999,
            'features' => 'Premium plan',
            'store_limit' => 5,
            'user_limit' => 10,
        ]);

        $this->createClientTenant('Alpha Retail', 'alpha@example.com', $starter->id);
        $this->createClientTenant('Beta Pharmacy', 'beta@example.com', $premium->id);

        $response = $this->actingAs($owner)->get(route('clients.index', [
            'search' => 'beta',
            'plan_id' => $premium->id,
        ]));

        $response->assertOk();
        $response->assertSee('Beta Pharmacy');
        $response->assertDontSee('Alpha Retail');
    }

    public function test_clients_listing_supports_per_page_pagination(): void
    {
        [$owner] = $this->tenantOwner(Tenant::TYPE_VENDOR, 'Vendor HQ', 'vendor@example.com');

        foreach (range(1, 26) as $index) {
            $this->createClientTenant('Client '.$index, 'client'.$index.'@example.com');
        }

        $response = $this->actingAs($owner)->get(route('clients.index', ['per_page' => 25]));

        $response->assertOk();
        $response->assertViewHas('perPage', 25);
        $response->assertViewHas('clients', fn ($clients) => $clients->perPage() === 25 && $clients->total() === 26);
        $response->assertSee('per_page=25', false);
    }

    private function tenantOwner(int $tenantType, string $businessName, string $email): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => $tenantType,
            'business_name' => $businessName,
            'owner_name' => 'Owner User',
            'mobile' => '+91 98765 43210',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner User',
            'email' => $email,
            'company_name' => $businessName,
            'phone' => '9876543210',
            'country_code' => '+91',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);

        return [$owner, $tenant];
    }

    private function createClientTenant(string $businessName, string $email, ?int $planId = null): Tenant
    {
        $planId ??= Plan::where('name', 'starter')->firstOrFail()->id;

        $tenant = Tenant::create([
            'plan_id' => $planId,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => $businessName,
            'owner_name' => $businessName.' Owner',
            'mobile' => '+91 90000 00000',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => $businessName.' road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $businessName.' Owner',
            'email' => $email,
            'company_name' => $businessName,
            'phone' => '9000000000',
            'country_code' => '+91',
            'plan' => $planId,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);

        return $tenant;
    }
}
