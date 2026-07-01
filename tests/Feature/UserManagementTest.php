<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_user_until_plan_limit(): void
    {
        [$owner, $tenant] = $this->tenantOwnerWithLimit(2);

        $response = $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'Sales Staff',
            'email' => 'sales@example.com',
            'phone' => '+91 99999 99999',
            'role' => User::ROLE_SALES_STAFF,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'sales@example.com',
            'role' => User::ROLE_SALES_STAFF,
        ]);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'user_created',
            'title' => 'User account created',
        ]);
    }

    public function test_plan_user_limit_blocks_extra_users(): void
    {
        [$owner] = $this->tenantOwnerWithLimit(1);

        $response = $this->actingAs($owner)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'name' => 'Blocked Staff',
                'email' => 'blocked@example.com',
                'role' => User::ROLE_MANAGER,
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('limit');
        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_duplicate_user_email_is_checked_inside_current_tenant(): void
    {
        [$owner] = $this->tenantOwnerWithLimit(3);

        User::create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Existing Staff',
            'email' => 'staff@example.com',
            'company_name' => 'Demo Store',
            'plan' => $owner->plan,
            'role' => User::ROLE_MANAGER,
            'password' => 'Password123',
        ]);

        $response = $this->actingAs($owner)
            ->from(route('users.index'))
            ->post(route('users.store'), [
                'name' => 'Duplicate Staff',
                'email' => 'STAFF@example.com',
                'role' => User::ROLE_SALES_STAFF,
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('email');
    }

    public function test_non_owner_user_can_be_deleted(): void
    {
        [$owner] = $this->tenantOwnerWithLimit(3);

        $staff = User::create([
            'tenant_id' => $owner->tenant_id,
            'name' => 'Staff User',
            'email' => 'staff-delete@example.com',
            'company_name' => 'Demo Store',
            'plan' => $owner->plan,
            'role' => User::ROLE_MANAGER,
            'password' => 'Password123',
        ]);

        $response = $this->actingAs($owner)->post(route('users.delete', $staff));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $owner->tenant_id,
            'type' => 'user_deleted',
            'title' => 'User account deleted',
        ]);
    }

    public function test_owner_user_cannot_be_deleted(): void
    {
        [$owner] = $this->tenantOwnerWithLimit(3);

        $response = $this->actingAs($owner)
            ->from(route('users.index'))
            ->delete(route('users.destroy', $owner));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    private function tenantOwnerWithLimit(?int $limit): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $plan->update(['user_limit' => $limit]);

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
