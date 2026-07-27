<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlanUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_expired_free_trial_owner_sees_upgrade_popup_after_login(): void
    {
        [$owner] = $this->expiredTrialAccount();

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Free trial completed')
            ->assertSee('Choose a plan to continue')
            ->assertSee('data-trial-upgrade-form', false)
            ->assertSee(route('plan.upgrade'), false)
            ->assertDontSee('Free Trial</strong>', false);
    }

    public function test_owner_can_upgrade_an_expired_free_trial_to_a_paid_plan(): void
    {
        Carbon::setTestNow('2026-07-27 10:00:00');
        [$owner, $tenant, $staff] = $this->expiredTrialAccount();
        $growth = Plan::where('name', 'growth')->firstOrFail();

        $response = $this->actingAs($owner)->postJson(route('plan.upgrade'), [
            'plan_id' => $growth->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Your account has been upgraded to Growth.');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'plan_id' => $growth->id,
            'domain_expired_date' => '2026-08-27 00:00:00',
        ]);
        $this->assertDatabaseHas('users', ['id' => $owner->id, 'plan' => $growth->id]);
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'plan' => $growth->id]);
        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $growth->id,
            'status' => 'active',
            'renews_at' => '2026-08-27',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-trial-upgrade-modal', false);
    }

    public function test_non_owner_cannot_upgrade_the_plan(): void
    {
        [, , $staff] = $this->expiredTrialAccount();
        $starter = Plan::where('name', 'starter')->firstOrFail();

        $this->actingAs($staff)->postJson(route('plan.upgrade'), [
            'plan_id' => $starter->id,
        ])->assertForbidden();
    }

    private function expiredTrialAccount(): array
    {
        $trial = Plan::where('name', 'free_trial')->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $trial->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => 'Expired Trial Store',
            'owner_name' => 'Trial Owner',
            'mobile' => '+91 98765 43210',
            'email' => 'trial-owner@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Trial road',
            'domain_expired_date' => now()->subDay()->toDateString(),
            'role_permissions' => RolePermission::defaults(),
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Trial Owner',
            'email' => 'trial-owner@example.com',
            'company_name' => $tenant->business_name,
            'plan' => $trial->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Trial Staff',
            'email' => 'trial-staff@example.com',
            'company_name' => $tenant->business_name,
            'plan' => $trial->id,
            'role' => User::ROLE_SALES_STAFF,
            'password' => 'Password123',
        ]);

        return [$owner, $tenant, $staff];
    }
}
