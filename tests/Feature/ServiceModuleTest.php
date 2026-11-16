<?php

namespace Tests\Feature;

use App\Models\{Customer, Plan, Product, ServiceOrder, Tenant, User};
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModuleTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email = 'service@example.com'): User
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create(['plan_id' => $plan->id, 'tenant_type' => Tenant::TYPE_CLIENT, 'business_name' => 'Service Store', 'owner_name' => 'Owner', 'mobile' => '9876543210', 'email' => $email, 'business_category' => Tenant::CATEGORY_RETAIL, 'store_address' => 'Main road', 'role_permissions' => RolePermission::defaults()]);
        return User::create(['tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => $email, 'password' => 'Password123', 'role' => User::ROLE_OWNER, 'plan' => $plan->id]);
    }

    private function order(User $owner): ServiceOrder
    {
        $customer = Customer::create(['tenant_id' => $owner->tenant_id, 'name' => 'Service Customer', 'mobile' => '9876543210']);
        return ServiceOrder::create(['tenant_id' => $owner->tenant_id, 'customer_id' => $customer->id, 'service_type' => 'Laptop repair', 'charge' => 1000]);
    }

    public function test_create_form_shows_required_errors_and_does_not_need_a_technician(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('services.index'))
            ->assertOk()->assertDontSee('name="technician_id"', false);
        $this->from(route('services.index'))->post(route('services.store'), [])
            ->assertRedirect(route('services.index'))
            ->assertSessionHasErrors([
                'customer_id' => 'Please select a customer.',
                'service_type' => 'Please enter a service type.',
                'charge' => 'Please enter the total customer charge.',
            ]);
        $this->get(route('services.index'))->assertOk()
            ->assertSee('Please select a customer.')
            ->assertSee('Please enter a service type.')
            ->assertSee('Please enter the total customer charge.');
        $this->assertDatabaseCount('service_orders', 0);
        $customer = Customer::create(['tenant_id' => $owner->tenant_id, 'name' => 'Repair Customer']);
        $this->post(route('services.store'), [
            'customer_id' => $customer->id, 'service_type' => 'Repair', 'charge' => 0,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull(ServiceOrder::firstOrFail()->technician_id);
    }

    public function test_ajax_service_creation_returns_validation_and_redirect(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->postJson(route('services.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['customer_id', 'service_type', 'charge']);
        $this->assertDatabaseCount('service_orders', 0);
        $customer = Customer::create(['tenant_id' => $owner->tenant_id, 'name' => 'Repair Customer']);
        $response = $this->postJson(route('services.store'), [
            'customer_id' => $customer->id, 'service_type' => 'Repair', 'charge' => 100,
        ]);
        $order = ServiceOrder::firstOrFail();
        $response->assertCreated()->assertJsonPath('redirect', route('services.show', $order));
        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_ajax_payment_and_expense_validation_and_updates(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner);
        $this->actingAs($owner)->postJson(route('services.payment', $order), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['amount', 'method']);
        $this->postJson(route('services.cost', $order), ['kind' => 'expense'])
            ->assertUnprocessable()->assertJsonValidationErrors(['description', 'quantity', 'unit_cost']);
        $this->postJson(route('services.payment', $order), ['amount' => 1001, 'method' => 'cash'])
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->postJson(route('services.payment', $order), ['amount' => 1000, 'method' => 'cash'])
            ->assertOk()->assertJsonPath('message', 'Payment received.')
            ->assertJsonStructure(['html']);
        $this->assertSame('1000.00', $order->fresh()->paid_amount);
        $this->postJson(route('services.cost', $order), [
            'kind' => 'expense', 'description' => 'Labour', 'quantity' => 2, 'unit_cost' => 50,
        ])->assertOk()->assertJsonPath('message', 'Service cost recorded.')->assertJsonStructure(['html']);
        $this->assertSame(100.0, $order->fresh()->total_cost);
        $this->assertSame(900.0, $order->fresh()->profit);
    }

    public function test_full_service_workflow_and_dashboard(): void
    {
        $owner = $this->owner();
        $customer = Customer::create(['tenant_id' => $owner->tenant_id, 'name' => 'Repair Customer']);
        $this->actingAs($owner)->get(route('services.index'))->assertOk()->assertSee('New service order');
        $this->post(route('services.store'), ['customer_id' => $customer->id, 'service_type' => 'Display replacement', 'charge' => 1000, 'technician_id' => $owner->id, 'due_date' => '2026-12-01'])->assertSessionHasNoErrors()->assertRedirect();
        $order = ServiceOrder::firstOrFail();
        $product = Product::create(['tenant_id' => $owner->tenant_id, 'user_id' => $owner->id, 'name' => 'Display', 'sku' => 'DISPLAY', 'price' => 400, 'purchase_price' => 200, 'inventory' => 5, 'status' => 'active']);
        $this->get(route('services.show', $order))->assertOk()
            ->assertDontSee('Consume inventory parts')->assertSee('Add service expense');
        $this->post(route('services.cost', $order), ['kind' => 'part', 'product_id' => $product->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->inventory);
        $this->assertDatabaseHas('stock_movements', ['type' => 'service', 'quantity' => -2, 'reference_id' => $order->id]);
        $product->update(['purchase_price' => 900]);
        $this->post(route('services.cost', $order), ['kind' => 'expense', 'description' => 'Travel', 'quantity' => 1, 'unit_cost' => 50])->assertSessionHasNoErrors();
        $this->post(route('services.payment', $order), ['amount' => 300, 'method' => 'cash'])->assertSessionHasNoErrors();
        $this->post(route('services.payment', $order), ['amount' => 701, 'method' => 'cash'])->assertSessionHasErrors('amount');
        $this->post(route('services.payment', $order), ['amount' => 700, 'method' => 'upi'])->assertSessionHasNoErrors();
        $this->patch(route('services.update', $order), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(550.0, $order->fresh()->profit);
        $this->assertSame('1000.00', $order->fresh()->paid_amount);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('stats', fn ($stats) => $stats['service_profit'] == 550 && $stats['net_profit'] == 550 && $stats['monthly_revenue'] == 1000);
        $this->post(route('services.cost', $order), ['kind' => 'part', 'product_id' => $product->id, 'quantity' => 1])->assertSessionHasErrors('kind');
        $this->assertSame(3, $product->fresh()->inventory);
    }

    public function test_stock_limits_and_tenant_boundaries(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner);
        $other = $this->owner('other@example.com');
        $foreignOrder = $this->order($other);
        $product = Product::create(['tenant_id' => $owner->tenant_id, 'user_id' => $owner->id, 'name' => 'Part', 'sku' => 'PART', 'price' => 20, 'purchase_price' => 10, 'inventory' => 5, 'reserved_stock' => 3, 'damaged_stock' => 1, 'status' => 'active']);
        $this->actingAs($owner)->post(route('services.cost', $order), ['kind' => 'part', 'product_id' => $product->id, 'quantity' => 2])->assertSessionHasErrors('quantity');
        $this->assertSame(5, $product->fresh()->inventory);
        $this->assertDatabaseCount('service_costs', 0);
        $this->get(route('services.show', $foreignOrder))->assertNotFound();
        $this->patch(route('services.update', $foreignOrder), ['status' => 'completed'])->assertNotFound();
        $this->post(route('services.payment', $foreignOrder), ['amount' => 1, 'method' => 'cash'])->assertNotFound();
        $this->post(route('services.store'), ['customer_id' => $foreignOrder->customer_id, 'service_type' => 'Repair', 'charge' => 100])->assertSessionHasErrors('customer_id');
        $this->post(route('services.store'), ['customer_id' => $order->customer_id, 'technician_id' => $other->id, 'service_type' => 'Repair', 'charge' => 100])->assertSessionHasErrors('technician_id');
        $owner->update(['role' => User::ROLE_WAREHOUSE_STAFF]);
        $this->get(route('services.index'))->assertForbidden();
        $this->post(route('services.payment', $order), ['amount' => 1, 'method' => 'cash'])->assertForbidden();
    }
}
