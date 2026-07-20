<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\SalesItem;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBestSellersTest extends TestCase
{
    use RefreshDatabase;

    public function test_best_sellers_ajax_filter_ranks_by_units_or_profit(): void
    {
        [$user, $tenant] = $this->user();
        $unitsLeader = $this->product($user, $tenant, 'Units Leader', 90);
        $profitLeader = $this->product($user, $tenant, 'Profit Leader', 10);
        $order = SalesOrder::create(['tenant_id' => $tenant->id, 'invoice_number' => 'BEST-001', 'subtotal' => 1000, 'total_amount' => 1000, 'paid_amount' => 1000]);

        SalesItem::create(['sales_order_id' => $order->id, 'product_id' => $unitsLeader->id, 'quantity' => 10, 'selling_price' => 100]);
        SalesItem::create(['sales_order_id' => $order->id, 'product_id' => $profitLeader->id, 'quantity' => 2, 'selling_price' => 500]);

        $this->actingAs($user)->getJson(route('dashboard.best-sellers', ['metric' => 'units']))
            ->assertOk()->assertJsonPath('html', fn (string $html) => str_contains($html, 'Units Leader') && str_contains($html, '10 sold'));

        $this->actingAs($user)->getJson(route('dashboard.best-sellers', ['metric' => 'profit']))
            ->assertOk()->assertJsonPath('html', fn (string $html) => str_contains($html, 'Profit Leader') && str_contains($html, '980 profit'));
    }

    private function user(): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();
        $tenant = Tenant::create(['plan_id' => $plan->id, 'tenant_type' => Tenant::TYPE_CLIENT, 'business_name' => 'Best Seller Store', 'owner_name' => 'Owner', 'mobile' => '+91 98765 43210', 'email' => 'best@example.com', 'business_category' => Tenant::CATEGORY_RETAIL, 'store_address' => 'Test road', 'role_permissions' => RolePermission::defaults()]);
        $user = User::create(['tenant_id' => $tenant->id, 'name' => 'Owner', 'email' => 'best@example.com', 'company_name' => 'Best Seller Store', 'phone' => '9876543210', 'country_code' => '+91', 'plan' => $plan->id, 'role' => User::ROLE_OWNER, 'password' => 'Password123']);

        return [$user, $tenant];
    }

    private function product(User $user, Tenant $tenant, string $name, int $purchasePrice): Product
    {
        return Product::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'name' => $name, 'sku' => str($name)->slug(), 'category' => 'General', 'purchase_price' => $purchasePrice, 'price' => 100, 'inventory' => 20, 'minimum_stock_level' => 2, 'status' => 'active']);
    }
}
