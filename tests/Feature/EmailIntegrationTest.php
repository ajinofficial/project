<?php

namespace Tests\Feature;

use App\Models\EmailIntegration;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantEmailSender;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_module_is_visible_and_accessible_only_to_vendor_accounts(): void
    {
        $vendor = $this->owner(Tenant::TYPE_VENDOR, 'vendor-email@example.com');
        $client = $this->owner(Tenant::TYPE_CLIENT, 'client-email@example.com');

        $this->actingAs($vendor)->get(route('vendor.dashboard'))
            ->assertOk()
            ->assertSee(route('email.index'), false);
        $this->actingAs($vendor)->get(route('email.index'))
            ->assertOk()
            ->assertSee('Email workspace');

        $this->actingAs($client)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('email.index'), false);
        $this->actingAs($client)->get(route('email.index'))->assertForbidden();
    }

    public function test_vendor_can_save_encrypted_smtp_settings_and_send_email(): void
    {
        $vendor = $this->owner(Tenant::TYPE_VENDOR, 'mail-sender@example.com');
        $password = 'secure-smtp-password';

        $this->actingAs($vendor)->put(route('email.update'), [
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer@example.com',
            'password' => $password,
            'from_address' => 'support@example.com',
            'from_name' => 'Vendor Support',
            'is_active' => '1',
        ])->assertSessionHas('status', 'Email connection saved.');

        $integration = EmailIntegration::firstOrFail();
        $this->assertSame($password, $integration->password);
        $this->assertStringNotContainsString($password, (string) $integration->getRawOriginal('password'));

        $this->mock(TenantEmailSender::class)
            ->shouldReceive('send')
            ->once()
            ->withArgs(fn ($settings, $recipient, $subject, $message) => $settings->is($integration)
                && $recipient === 'customer@example.com'
                && $subject === 'Account update'
                && $message === 'Your account has been updated.');

        $this->actingAs($vendor)->post(route('email.send'), [
            'recipient' => 'customer@example.com',
            'subject' => 'Account update',
            'message' => 'Your account has been updated.',
        ])->assertSessionHas('status', 'Email sent successfully.');

        $this->assertDatabaseHas('email_messages', [
            'tenant_id' => $vendor->tenant_id,
            'recipient' => 'customer@example.com',
            'subject' => 'Account update',
            'status' => 'sent',
        ]);
    }

    private function owner(int $tenantType, string $email): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => $tenantType,
            'business_name' => $tenantType === Tenant::TYPE_VENDOR ? 'Vendor HQ' : 'Client Store',
            'owner_name' => 'Owner',
            'mobile' => '9999999999',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Test road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => $email,
            'company_name' => $tenant->business_name,
            'phone' => '9999999999',
            'plan' => $plan->id,
            'role' => User::ROLE_OWNER,
            'password' => 'Password123',
        ]);
    }
}
