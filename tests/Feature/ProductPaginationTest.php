<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_paginates_products_and_preserves_query_string(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other@example.com');

        foreach (range(1, 30) as $number) {
            Product::create([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'name' => sprintf('Product %02d', $number),
                'sku' => sprintf('SKU-%02d', $number),
                'category' => 'General',
                'purchase_price' => 50,
                'price' => 100,
                'tax_percentage' => 18,
                'inventory' => 20,
                'minimum_stock_level' => 10,
                'status' => 'active',
            ]);
        }

        Product::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $owner->id,
            'name' => 'Product 999',
            'sku' => 'SKU-999',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.index', [
            'sort' => 'name',
            'per_page' => 25,
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('Showing 26-30 of 30 products');
        $response->assertSee('Product 26');
        $response->assertDontSee('Product 25');
        $response->assertDontSee('Product 999');
        $response->assertSee('per_page=25', false);
        $response->assertSee('sort=name', false);
    }

    public function test_invalid_product_page_size_falls_back_to_default(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        foreach (range(1, 11) as $number) {
            Product::create([
                'tenant_id' => $tenant->id,
                'user_id' => $owner->id,
                'name' => sprintf('Fallback Product %02d', $number),
                'sku' => sprintf('FB-%02d', $number),
                'category' => 'General',
                'purchase_price' => 50,
                'price' => 100,
                'tax_percentage' => 18,
                'inventory' => 20,
                'minimum_stock_level' => 10,
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($owner)->get(route('products.index', [
            'sort' => 'name',
            'per_page' => 500,
        ]));

        $response->assertOk();
        $response->assertSee('Showing 1-10 of 11 products');
        $response->assertSee('10 / page');
    }

    public function test_product_pagination_is_visible_for_single_product(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Single Product',
            'sku' => 'ONE-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('Showing 1-1 of 1 products');
        $response->assertSee('aria-current="page">1</span>', false);
        $response->assertSee('aria-label="Previous page"', false);
        $response->assertSee('aria-label="Next page"', false);
    }

    public function test_product_filters_auto_submit_and_include_clear_link(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Filtered Product',
            'sku' => 'FILTER-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.index', [
            'search' => 'Filtered',
            'status' => 'active',
        ]));

        $response->assertOk();
        $response->assertSee('data-product-filters', false);
        $response->assertSee('data-auto-filter', false);
        $response->assertSee('data-product-listing-loader', false);
        $response->assertSee('Loading products');
        $response->assertSee('href="'.route('products.index').'"', false);
        $response->assertSee('Clear');
        $response->assertDontSee('>Filter</button>', false);
    }

    public function test_empty_catalog_prompts_user_to_add_product(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('No products yet');
        $response->assertSee('Add your first product to start managing inventory.');
        $response->assertSee('Add product');
        $response->assertDontSee('No matching products');
    }

    public function test_empty_filtered_result_prompts_user_to_clear_filters(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Existing Product',
            'sku' => 'EXIST-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.index', [
            'search' => 'Missing Product',
        ]));

        $response->assertOk();
        $response->assertSee('No matching products');
        $response->assertSee('Clear filters');
        $response->assertDontSee('No products yet');
    }

    public function test_product_create_form_lists_tenant_suppliers(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other-supplier@example.com');

        Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Local Supplier',
        ]);

        Supplier::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Supplier',
        ]);

        $response = $this->actingAs($owner)->get(route('products.create'));

        $response->assertOk();
        $response->assertSee('name="supplier_id"', false);
        $response->assertSee('data-product-save-form', false);
        $response->assertSee('data-product-save-button', false);
        $response->assertSee('data-replace-on-focus', false);
        $response->assertSee('Saving');
        $response->assertSee('Local Supplier');
        $response->assertDontSee('Other Tenant Supplier');
    }

    public function test_product_edit_form_has_submit_loader(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Editable Product',
            'sku' => 'EDIT-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.edit', $product));

        $response->assertOk();
        $response->assertSee('data-product-save-form', false);
        $response->assertSee('data-product-save-button', false);
        $response->assertSee('Updating');
    }

    public function test_product_store_saves_supplier_id(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Primary Supplier',
        ]);

        $response = $this->actingAs($owner)->post(route('products.store'), $this->productPayload([
            'supplier_id' => $supplier->id,
        ]));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Stored Product',
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_product_store_rejects_supplier_from_another_tenant(): void
    {
        [$owner] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('blocked-supplier@example.com');

        $supplier = Supplier::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Blocked Supplier',
        ]);

        $response = $this->actingAs($owner)
            ->from(route('products.create'))
            ->post(route('products.store'), $this->productPayload([
                'supplier_id' => $supplier->id,
            ]));

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors('supplier_id');
        $this->assertDatabaseMissing('products', ['name' => 'Stored Product']);
    }

    public function test_product_index_shows_blocked_delete_popup_for_active_product(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Active Product',
            'sku' => 'ACTIVE-DELETE-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('products.index'));

        $response->assertOk();
        $response->assertSee('data-confirm-blocked="true"', false);
        $response->assertSee('Cannot delete active product');
        $response->assertSee('Active products cannot be deleted. Archive Active Product first.');
    }

    public function test_active_product_delete_is_rejected(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Active Delete Product',
            'sku' => 'ACTIVE-DELETE-02',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->delete(route('products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Active products cannot be deleted. Archive the product first.');
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_status' => 0,
        ]);
    }

    public function test_inactive_product_delete_marks_deleted_status_and_hides_product(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Deleted Product',
            'sku' => 'DELETE-01',
            'category' => 'General',
            'purchase_price' => 50,
            'price' => 100,
            'tax_percentage' => 18,
            'inventory' => 20,
            'minimum_stock_level' => 10,
            'status' => 'archived',
        ]);

        $response = $this->actingAs($owner)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_status' => 1,
        ]);

        $this->assertSame(0, Product::whereKey($product->id)->count());
    }

    private function tenantOwner(string $email = 'owner@example.com'): array
    {
        $plan = Plan::where('name', 'starter')->firstOrFail();

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'tenant_type' => Tenant::TYPE_VENDOR,
            'business_name' => 'Demo Store',
            'owner_name' => 'Owner User',
            'mobile' => '+91 98765 43210',
            'email' => $email,
            'business_category' => Tenant::CATEGORY_RETAIL,
            'store_address' => 'Demo road',
            'role_permissions' => RolePermission::defaults(),
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner User',
            'email' => $email,
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
            'name' => 'Stored Product',
            'sku' => 'STORED-01',
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
