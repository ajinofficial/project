<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_shows_all_and_unread_filters(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Notification::create([
            'tenant_id' => $tenant->id,
            'type' => 'activity',
            'channel' => 'in_app',
            'title' => 'Read notification',
            'message' => 'Already read.',
            'read_at' => now(),
        ]);

        Notification::create([
            'tenant_id' => $tenant->id,
            'type' => 'activity',
            'channel' => 'in_app',
            'title' => 'Unread notification',
            'message' => 'Needs attention.',
        ]);

        $response = $this->actingAs($owner)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('All');
        $response->assertSee('Unread');
        $response->assertSee('Read');
        $response->assertSee('Need attention');
        $response->assertSee('Already reviewed');
        $response->assertSee('Read notification');
        $response->assertSee('Unread notification');

        $unreadResponse = $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'unread']));

        $unreadResponse->assertOk();
        $unreadResponse->assertSee('Unread notification');
        $unreadResponse->assertDontSee('<h3>Read notification</h3>', false);

        $readResponse = $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'read']));

        $readResponse->assertOk();
        $readResponse->assertSee('Read notification');
        $readResponse->assertDontSee('<h3>Unread notification</h3>', false);
    }

    public function test_mark_read_preserves_unread_filter(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $notification = Notification::create([
            'tenant_id' => $tenant->id,
            'type' => 'activity',
            'channel' => 'in_app',
            'title' => 'Unread notification',
            'message' => 'Needs attention.',
        ]);

        $response = $this->actingAs($owner)->patch(route('notifications.read', [
            'notification' => $notification,
            'filter' => 'unread',
        ]));

        $response->assertRedirect(route('notifications.index', ['filter' => 'unread']));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function tenantOwner(): array
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
