<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_improved_role_permission_page(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)->get(route('role-permissions.index'));

        $response->assertOk();
        $response->assertSee('Manage menu access');
        $response->assertSee('Select all');
        $response->assertSee('Use default');
        $response->assertSee('>Edit</span>', false);
        $response->assertSee('Save permissions');
        $response->assertDontSee('data-permission-save-button', false);
        $response->assertSee('data-summary-meter="owner"', false);
        $response->assertSee('/ 13', false);
        $response->assertDontSee('role-permissions.index');
        $response->assertDontSee('Vendor Dashboard');
        $response->assertDontSee('Clients');
    }

    public function test_owner_can_update_role_permissions(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('role-permissions.index'))
            ->put(route('role-permissions.update'), [
                'permissions' => [
                    'manager' => ['dashboard', 'inventory', 'billing'],
                    'sales_staff' => ['dashboard', 'billing'],
                    'warehouse_staff' => ['dashboard', 'inventory', 'purchases'],
                    'accountant' => ['dashboard', 'reports'],
                ],
            ]);

        $response->assertRedirect(route('role-permissions.index'));
        $response->assertSessionHas('status', 'Role permissions updated.');

        $permissions = $tenant->refresh()->role_permissions;

        $this->assertSame(['dashboard', 'inventory', 'billing'], $permissions['manager']);
        $this->assertSame(['dashboard', 'billing'], $permissions['sales_staff']);
        $this->assertSame(RolePermission::configurableMenuKeys(), $permissions['owner']);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'role_permissions_updated',
            'title' => 'Role permissions updated',
            'message' => 'Owner User updated permissions for Manager, Sales staff, Warehouse staff, and Accountant.',
        ]);
    }

    public function test_role_permission_update_rejects_invalid_menu(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('role-permissions.index'))
            ->put(route('role-permissions.update'), [
                'permissions' => [
                    'manager' => ['dashboard', 'not-a-menu'],
                ],
            ]);

        $response->assertRedirect(route('role-permissions.index'));
        $response->assertSessionHasErrors('permissions.manager.1');
    }

    private function tenantOwner(): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => 'Demo Store',
            'owner_name' => 'Owner User',
            'mobile' => '+91 98765 43210',
            'email' => 'owner@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'company_name' => 'Demo Store',
            'phone' => '+91 98765 43210',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);

        return [$owner, $tenant];
    }
}
