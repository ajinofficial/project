<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsAppIntegration;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_is_visible_and_accessible_only_on_growth_plan(): void
    {
        $growthOwner = $this->ownerForPlan('growth', 'growth@example.com');
        $starterOwner = $this->ownerForPlan('starter', 'starter-whatsapp@example.com');

        $this->actingAs($growthOwner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('whatsapp.index'), false);
        $this->actingAs($growthOwner)->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('WhatsApp messaging');

        $this->actingAs($starterOwner)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('whatsapp.index'), false);
        $this->actingAs($starterOwner)->get(route('whatsapp.index'))->assertForbidden();
    }

    public function test_owner_can_save_encrypted_connection_and_send_message(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.TEST-123']],
            ]),
        ]);
        $owner = $this->ownerForPlan('growth', 'sender@example.com');
        $token = str_repeat('secure-token-', 4);

        $this->actingAs($owner)->put(route('whatsapp.update'), [
            'business_account_id' => 'business-123',
            'phone_number_id' => 'phone-456',
            'access_token' => $token,
            'is_active' => '1',
        ])->assertSessionHas('status', 'WhatsApp connection saved.');

        $integration = WhatsAppIntegration::firstOrFail();
        $this->assertSame($token, $integration->access_token);
        $this->assertStringNotContainsString($token, (string) $integration->getRawOriginal('access_token'));

        $this->actingAs($owner)->post(route('whatsapp.send'), [
            'recipient' => '919876543210',
            'message' => 'Your order is ready.',
        ])->assertSessionHas('status', 'WhatsApp message sent.');

        $this->assertDatabaseHas('whatsapp_messages', [
            'tenant_id' => $owner->tenant_id,
            'recipient' => '919876543210',
            'status' => 'sent',
            'provider_message_id' => 'wamid.TEST-123',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v22.0/phone-456/messages'
            && $request->hasHeader('Authorization', 'Bearer '.$token)
            && $request['to'] === '919876543210'
            && $request['text']['body'] === 'Your order is ready.');
    }

    private function ownerForPlan(string $planName, string $email): User
    {
        $plan = Plan::where('name', $planName)->firstOrFail();
        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => ucfirst($planName).' Store',
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
