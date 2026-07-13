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
            'type' => 'stock_alert',
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
        $response->assertSee('data-notification-filter-form', false);
        $response->assertSee('data-notification-search', false);
        $response->assertSee('data-notification-type', false);
        $response->assertSee('setTimeout(submitFilters, 400)', false);
        $response->assertSee('notification-read-all-button', false);
        $response->assertSee('Mark all notifications as read?', false);

        $unreadResponse = $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'unread']));

        $unreadResponse->assertOk();
        $unreadResponse->assertSee('Unread notification');
        $unreadResponse->assertDontSee('<h3>Read notification</h3>', false);

        $readResponse = $this->actingAs($owner)->get(route('notifications.index', ['filter' => 'read']));

        $readResponse->assertOk();
        $readResponse->assertSee('Read notification');
        $readResponse->assertDontSee('<h3>Unread notification</h3>', false);

        $searchResponse = $this->actingAs($owner)->get(route('notifications.index', [
            'search' => 'Needs attention',
            'type' => 'activity',
        ]));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Unread notification');
        $searchResponse->assertDontSee('<h3>Read notification</h3>', false);
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

    public function test_mark_unread_preserves_active_notification_filters(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $notification = Notification::create([
            'tenant_id' => $tenant->id,
            'type' => 'activity',
            'channel' => 'in_app',
            'title' => 'Reviewed notification',
            'message' => 'Review this again later.',
            'read_at' => now(),
        ]);

        $response = $this->actingAs($owner)->patch(route('notifications.unread', $notification), [
            'filter' => 'read',
            'search' => 'Reviewed',
            'type' => 'activity',
        ]);

        $response->assertRedirect(route('notifications.index', [
            'filter' => 'read',
            'search' => 'Reviewed',
            'type' => 'activity',
        ]));
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notifications_page_exposes_infinite_scroll_pagination_and_loader(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        foreach (range(1, 16) as $number) {
            Notification::create([
                'tenant_id' => $tenant->id,
                'type' => 'activity',
                'channel' => 'in_app',
                'title' => 'Scroll notification '.$number,
                'message' => 'Infinite scroll test item.',
            ]);
        }

        $response = $this->actingAs($owner)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('data-notification-list', false);
        $response->assertSee('data-notification-pagination', false);
        $response->assertSee('data-next-page="http://localhost/notifications?page=2"', false);
        $response->assertSee('data-notification-loader', false);
        $response->assertSee('Loading notifications');
        $response->assertSee('IntersectionObserver', false);
        $this->assertSame(15, substr_count($response->getContent(), '<article class="notification-page-item is-unread">'));
    }

    public function test_mark_all_read_updates_unread_notifications_and_shows_caught_up_state(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        foreach (range(1, 2) as $number) {
            Notification::create([
                'tenant_id' => $tenant->id,
                'type' => 'activity',
                'channel' => 'in_app',
                'title' => 'Bulk notification '.$number,
                'message' => 'Pending bulk action.',
            ]);
        }

        $response = $this->actingAs($owner)->patch(route('notifications.readAll'), [
            'filter' => 'unread',
        ]);

        $response->assertRedirect(route('notifications.index', ['filter' => 'unread']));
        $this->assertSame(0, Notification::where('tenant_id', $tenant->id)->whereNull('read_at')->count());

        $followUp = $this->actingAs($owner)->get(route('notifications.index'));
        $followUp->assertOk();
        $followUp->assertSee('All caught up');
        $followUp->assertDontSee('notification-read-all-button', false);
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
