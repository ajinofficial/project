<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_store_setup_page(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)->get(route('setup.index'));

        $response->assertOk();
        $response->assertSee('Invoice and tax setup');
        $response->assertSee('Invoice preview');
        $response->assertSee('Role permissions');
    }

    public function test_owner_can_update_store_setup_defaults(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $response = $this->actingAs($owner)->put(route('setup.update'), [
            'currency' => 'INR',
            'default_tax_percentage' => '12.50',
            'low_stock_threshold' => 25,
            'invoice_prefix' => 'mw-2026',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Store setup saved.');

        $tenant->refresh();

        $this->assertSame('INR', $tenant->currency);
        $this->assertSame('12.50', $tenant->default_tax_percentage);
        $this->assertSame(25, $tenant->low_stock_threshold);
        $this->assertSame('MW-2026', $tenant->invoice_prefix);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'setup_updated',
            'title' => 'Store setup updated',
        ]);
    }

    public function test_setup_rejects_invalid_invoice_prefix(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('setup.index'))
            ->put(route('setup.update'), [
                'currency' => 'INR',
                'default_tax_percentage' => '18',
                'low_stock_threshold' => 10,
                'invoice_prefix' => 'INV 2026',
            ]);

        $response->assertRedirect(route('setup.index'));
        $response->assertSessionHasErrors('invoice_prefix');
    }

    private function tenantOwner(): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => 'Mobile World',
            'owner_name' => 'Ajay Kumar',
            'mobile' => '+91 98765 43210',
            'email' => 'owner@example.com',
            'business_category' => Tenant::CATEGORY_MOBILE,
            'store_address' => 'Shop 12, MG Road, Bengaluru',
            'currency' => 'INR',
            'default_tax_percentage' => 18,
            'low_stock_threshold' => 10,
            'invoice_prefix' => 'INV',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Ajay Kumar',
            'email' => 'owner@example.com',
            'company_name' => 'Mobile World',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);

        return [$owner, $tenant];
    }
}
