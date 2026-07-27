<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_sees_email_edit_controls(): void
    {
        [$owner] = $this->account('owner@example.com');

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-email-edit-open', false)
            ->assertSee('data-email-edit-form', false)
            ->assertSee(route('account.email.update'), false);
    }

    public function test_owner_can_update_email_before_verification(): void
    {
        [$owner, $tenant] = $this->account('owner@example.com');
        $owner->forceFill([
            'email_verified' => true,
            'email_verified_at' => now(),
        ])->save();

        $response = $this->actingAs($owner)->patchJson(route('account.email.update'), [
            'email' => ' NEW-OWNER@EXAMPLE.COM ',
        ]);

        $response->assertOk()
            ->assertJson([
                'email' => 'new-owner@example.com',
                'message' => 'Email updated. Send a new OTP to verify it.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
            'email' => 'new-owner@example.com',
            'email_verified' => false,
            'email_verified_at' => null,
        ]);
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'email' => 'new-owner@example.com',
        ]);
    }

    public function test_email_update_rejects_an_already_registered_email(): void
    {
        [$owner] = $this->account('owner@example.com');
        $this->account('existing@example.com', 'Existing Store');

        $this->actingAs($owner)->patchJson(route('account.email.update'), [
            'email' => 'EXISTING@EXAMPLE.COM',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'This email address is already registered.');

        $this->assertSame('owner@example.com', $owner->fresh()->email);
    }

    private function account(string $email, string $business = 'Email Edit Store'): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => $business,
            'owner_name' => 'Owner',
            'mobile' => '+91 98765 43210',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Test road',
            'role_permissions' => RolePermission::defaults(),
        ]);
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => $email,
            'company_name' => $business,
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);

        return [$owner, $tenant];
    }
}
