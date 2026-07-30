<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_stock_module_and_see_connected_flow(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Ledger Product',
            'sku' => 'LEDGER-01',
            'inventory' => 4,
            'minimum_stock_level' => 5,
            'image_url' => 'http://localhost/uploads/stock/ledger-product.jpg',
        ]));

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 4,
            'stock_after' => 4,
            'notes' => 'Opening purchase.',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('stock.index'));

        $response->assertOk();
        $response->assertSee('Stock movement ledger');
        $response->assertSee('Purchases add stock. Billing reduces stock. Returns and adjustments update stock.');
        $response->assertSee('Ledger Product');
        $response->assertSee('<img src="http://localhost/uploads/stock/ledger-product.jpg" alt="">', false);
        $response->assertSee('Opening purchase.');
        $response->assertSee('Low stock watchlist');
        $response->assertSee('data-stock-listing', false);
        $response->assertSee('data-stock-listing-results', false);
        $response->assertSee('data-stock-listing-loader', false);
        $response->assertSee('Loading movements');
        $response->assertSee(route('stock.create'), false);
    }

    public function test_owner_can_open_separate_add_stock_form(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Add Form Product',
            'sku' => 'ADD-STOCK-01',
        ]));

        $response = $this->actingAs($owner)->get(route('stock.create'));

        $response->assertOk();
        $response->assertSee('Stock add form');
        $response->assertSee('Add Form Product');
        $response->assertSee('data-searchable-select', false);
        $response->assertSee('data-searchable-select-input', false);
        $response->assertSee('data-searchable-select-menu', false);
        $response->assertSee('data-searchable-select-url="'.route('stock.products').'"', false);
        $response->assertSee('Loading products');
        $response->assertSee('loadRemoteProducts');
        $response->assertSee('Search or select product');
        $response->assertSee('name="adjustment"', false);
        $response->assertSee('name="stock_date"', false);
        $response->assertSee('max="'.now()->toDateString().'"', false);
        $response->assertSee('name="purchase_price"', false);
        $response->assertSee('name="profit_percentage"', false);
        $response->assertSee('name="profit_percentage" min="0" max="100"', false);
        $response->assertSee('Selling price after profit');
        $response->assertSee('novalidate', false);
        $response->assertSee('is required.', false);
    }

    public function test_stock_product_selector_is_tenant_scoped_and_paginated(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        $otherTenant = Tenant::create([
            'plan_id' => Plan::where('name', 'starter')->firstOrFail()->id,
            'tenant_type' => Tenant::TYPE_CLIENT,
            'business_name' => 'Other Store',
            'owner_name' => 'Other Owner',
            'mobile' => '+91 90000 00000',
            'email' => 'other-owner@example.com',
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Other road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        foreach (range(1, 25) as $number) {
            Product::create($this->productPayload([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'name' => 'Paged Product '.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'sku' => 'PAGE-'.$number,
            ]));
        }

        Product::create($this->productPayload([
            'tenant_id' => $otherTenant->id,
            'user_id' => $owner->id,
            'name' => 'Other Tenant Secret',
            'sku' => 'SECRET-1',
        ]));

        $this->actingAs($owner)->getJson(route('stock.products'))
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.has_more', true)
            ->assertJsonMissing(['label' => 'Other Tenant Secret - SECRET-1 - Stock 20']);

        $this->actingAs($owner)->getJson(route('stock.products', ['page' => 2]))
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.has_more', true);

        $this->actingAs($owner)->getJson(route('stock.products', ['page' => 3]))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.has_more', false);

        $this->actingAs($owner)->getJson(route('stock.products', ['search' => 'PAGE-25']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', Product::where('tenant_id', $tenant->id)->where('sku', 'PAGE-25')->value('id'));
    }

    public function test_stock_adjustment_updates_inventory_and_records_movement(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Adjustable Product',
            'inventory' => 10,
        ]));

        $response = $this->actingAs($owner)
            ->from(route('stock.index'))
            ->post(route('stock.adjust'), [
                'product_id' => $product->id,
                'adjustment' => -3,
                'stock_date' => now()->toDateString(),
                'notes' => 'Cycle count correction.',
            ]);

        $response->assertRedirect(route('stock.index'));
        $this->assertSame(7, $product->fresh()->inventory);
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => -3,
            'stock_after' => 7,
            'notes' => 'Cycle count correction.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'stock_adjusted',
            'title' => 'Stock adjusted',
        ]);
    }

    public function test_stock_add_updates_purchase_price_selling_price_and_records_pricing_flow(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Priced Stock Product',
            'inventory' => 10,
            'purchase_price' => 50,
            'price' => 100,
        ]));

        $response = $this->actingAs($owner)
            ->from(route('stock.create'))
            ->post(route('stock.adjust'), [
                'product_id' => $product->id,
                'adjustment' => 5,
                'stock_date' => '2025-07-10',
                'purchase_price' => 80,
                'profit_percentage' => 25,
                'notes' => 'Supplier refill.',
            ]);

        $response->assertRedirect(route('stock.index'));

        $product->refresh();

        $this->assertSame(15, $product->inventory);
        $this->assertSame('60.00', $product->purchase_price);
        $this->assertSame('100.00', $product->price);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 5,
            'stock_after' => 15,
            'notes' => 'Supplier refill. Purchase price: 80.00. Profit: 25.00%. Selling price: 100.00.',
        ]);
        $this->assertSame('2025-07-10', StockMovement::where('product_id', $product->id)->firstOrFail()->created_at->toDateString());
    }

    public function test_stock_movement_filters_by_search_and_type(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $matchedProduct = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Filtered Widget',
            'sku' => 'FILTER-01',
        ]));

        $otherProduct = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Other Widget',
            'sku' => 'OTHER-01',
        ]));

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'product_id' => $matchedProduct->id,
            'type' => 'purchase',
            'quantity' => 8,
            'stock_after' => 8,
            'notes' => 'Filter target note.',
            'user_id' => $owner->id,
        ]);

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'product_id' => $otherProduct->id,
            'type' => 'sale',
            'quantity' => -2,
            'stock_after' => 6,
            'notes' => 'Should not show.',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('stock.index', [
            'search' => 'filter',
            'type' => 'purchase',
        ]));

        $response->assertOk();
        $response->assertSee('data-stock-search-form', false);
        $response->assertSee('data-stock-search', false);
        $response->assertSee('window.stockListingAjaxBound', false);
        $response->assertSee('fetch(url', false);
        $response->assertSee("'X-Requested-With': 'XMLHttpRequest'", false);
        $response->assertSee("window.addEventListener('popstate'", false);
        $response->assertSee('Filtered Widget');
        $response->assertSee('Filter target note.');
        $response->assertDontSee('Other Widget');
        $response->assertDontSee('Should not show.');
    }

    public function test_stock_adjustment_cannot_make_inventory_negative(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'inventory' => 2,
        ]));

        $response = $this->actingAs($owner)
            ->from(route('stock.index'))
            ->post(route('stock.adjust'), [
                'product_id' => $product->id,
                'adjustment' => -3,
                'stock_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('stock.index'));
        $response->assertSessionHasErrors('adjustment');
        $this->assertSame(2, $product->fresh()->inventory);
        $this->assertSame(0, StockMovement::where('tenant_id', $tenant->id)->count());
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

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Stock Product',
            'sku' => 'STOCK-01',
            'barcode' => null,
            'category' => 'General',
            'brand' => null,
            'supplier_id' => null,
            'purchase_price' => 50,
            'price' => 100,
            'compare_at_price' => null,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'reserved_stock' => 0,
            'damaged_stock' => 0,
            'returned_stock' => 0,
            'status' => 'active',
            'image_url' => null,
            'description' => null,
        ], $overrides);
    }
}
