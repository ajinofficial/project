<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\SalesItem;
use App\Models\SalesOrder;
use App\Models\StockMovement;
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
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'product_created',
            'title' => 'Product created',
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

    public function test_supplier_listing_search_paginates_and_preserves_query_string(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other-filtered-supplier@example.com');

        foreach (range(1, 14) as $number) {
            Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => sprintf('Filtered Supplier %02d', $number),
                'contact_information' => 'filter-contact@example.com',
                'gst_number' => sprintf('GST-FILTER-%02d', $number),
                'payment_terms' => 'Net 15',
                'outstanding_balance' => 0,
            ]);
        }

        Supplier::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Filtered Supplier 999',
            'contact_information' => 'filter-contact@example.com',
            'gst_number' => 'GST-FILTER-999',
            'payment_terms' => 'Net 15',
            'outstanding_balance' => 0,
        ]);

        $response = $this->actingAs($owner)->get(route('suppliers.index', [
            'per_page' => 10,
            'search' => 'Filtered',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('data-supplier-search-form', false);
        $response->assertSee('data-supplier-search', false);
        $response->assertSee('10 / page');
        $response->assertSee('Showing 11-14 of 14 suppliers');
        $response->assertSee('per_page=10', false);
        $response->assertSee('search=Filtered', false);
        $response->assertSee('Filtered Supplier 11');
        $response->assertDontSee('Filtered Supplier 999');
    }

    public function test_supplier_form_shows_required_validation_errors(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('suppliers.index'))
            ->post(route('suppliers.store'), [
                'name' => null,
                'outstanding_balance' => null,
            ]);

        $response->assertRedirect(route('suppliers.index'));
        $response->assertSessionHasErrors(['name', 'outstanding_balance']);

        $followUp = $this->actingAs($owner)->get(route('suppliers.index'));

        $followUp->assertOk();
        $followUp->assertSee('data-supplier-form', false);
        $followUp->assertSee('data-supplier-submit', false);
        $followUp->assertSee('Check the supplier details');
        $followUp->assertSee('Enter the supplier name.');
        $followUp->assertSee('Enter the outstanding balance.');
    }

    public function test_customer_listing_search_paginates_and_preserves_query_string(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other-filtered-customer@example.com');

        foreach (range(1, 14) as $number) {
            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => sprintf('Filtered Customer %02d', $number),
                'mobile' => sprintf('90000000%02d', $number),
                'credit_limit' => 0,
                'outstanding_balance' => 0,
            ]);
        }

        Customer::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Filtered Customer 999',
            'mobile' => '9000000999',
            'credit_limit' => 0,
            'outstanding_balance' => 0,
        ]);

        $response = $this->actingAs($owner)->get(route('customers.index', [
            'per_page' => 10,
            'search' => 'Filtered',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('data-customer-search-form', false);
        $response->assertSee('data-customer-search', false);
        $response->assertSee('10 / page');
        $response->assertSee('Showing 11-14 of 14 customers');
        $response->assertSee('per_page=10', false);
        $response->assertSee('search=Filtered', false);
        $response->assertSee('Filtered Customer 11');
        $response->assertDontSee('Filtered Customer 999');
    }

    public function test_customer_form_shows_required_validation_errors(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => null,
                'credit_limit' => null,
                'outstanding_balance' => null,
            ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHasErrors(['name', 'credit_limit', 'outstanding_balance']);

        $followUp = $this->actingAs($owner)->get(route('customers.index'));

        $followUp->assertOk();
        $followUp->assertSee('data-customer-form', false);
        $followUp->assertSee('data-customer-submit', false);
        $followUp->assertSee('Check the customer details');
        $followUp->assertSee('Enter the customer name.');
        $followUp->assertSee('Enter the credit limit.');
        $followUp->assertSee('Enter the outstanding balance.');
    }

    public function test_return_listing_filters_paginates_and_preserves_query_string(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other-return@example.com');

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Filtered Return Product',
            'sku' => 'RET-FILTER',
        ]));

        foreach (range(1, 12) as $number) {
            StockMovement::create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'type' => 'sales_return',
                'quantity' => 1,
                'stock_after' => 20 + $number,
                'notes' => sprintf('Filtered return note %02d', $number),
                'user_id' => $owner->id,
            ]);
        }

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => 'purchase_return',
            'quantity' => -1,
            'stock_after' => 10,
            'notes' => 'Filtered purchase return',
            'user_id' => $owner->id,
        ]);

        StockMovement::create([
            'tenant_id' => $otherTenant->id,
            'product_id' => $product->id,
            'type' => 'sales_return',
            'quantity' => 1,
            'stock_after' => 99,
            'notes' => 'Filtered return other tenant',
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get(route('returns.index', [
            'per_page' => 10,
            'search' => 'Filtered',
            'type' => 'sales_return',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('data-return-search-form', false);
        $response->assertSee('data-return-search', false);
        $response->assertSee('10 / page');
        $response->assertSee('Showing 11-12 of 12 returns');
        $response->assertSee('per_page=10', false);
        $response->assertSee('search=Filtered', false);
        $response->assertSee('type=sales_return', false);
        $response->assertSee('Filtered return note 11');
        $response->assertDontSee('Filtered purchase return');
        $response->assertDontSee('Filtered return other tenant');
    }

    public function test_return_form_shows_required_validation_errors(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('returns.index'))
            ->post(route('returns.store'), [
                'product_id' => null,
                'return_type' => null,
                'quantity' => 0,
            ]);

        $response->assertRedirect(route('returns.index'));
        $response->assertSessionHasErrors(['product_id', 'return_type', 'quantity']);

        $followUp = $this->actingAs($owner)->get(route('returns.index'));

        $followUp->assertOk();
        $followUp->assertSee('data-return-form', false);
        $followUp->assertSee('data-return-submit', false);
        $followUp->assertSee('Check the return details');
        $followUp->assertSee('Select a product for the return.');
        $followUp->assertSee('Select the return type.');
        $followUp->assertSee('Quantity must be at least 1.');
    }

    public function test_purchase_return_cannot_exceed_available_stock(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Low Stock Return Product',
            'inventory' => 2,
        ]));

        $response = $this->actingAs($owner)
            ->from(route('returns.index'))
            ->post(route('returns.store'), [
                'product_id' => $product->id,
                'return_type' => 'purchase_return',
                'quantity' => 3,
            ]);

        $response->assertRedirect(route('returns.index'));
        $response->assertSessionHasErrors('quantity');
        $this->assertSame(2, $product->fresh()->inventory);
        $this->assertSame(0, StockMovement::where('tenant_id', $tenant->id)->count());
    }

    public function test_reports_filter_by_date_range_and_tenant(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [$otherOwner, $otherTenant] = $this->tenantOwner('other-report@example.com');

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Range Report Product',
            'purchase_price' => 40,
            'price' => 100,
        ]));

        $otherProduct = Product::create($this->productPayload([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherOwner->id,
            'name' => 'Other Tenant Report Product',
            'sku' => 'OTHER-REPORT',
        ]));

        $inRangeOrder = SalesOrder::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'RANGE-001',
            'subtotal' => 200,
            'tax_amount' => 0,
            'total_amount' => 200,
            'paid_amount' => 200,
            'payment_method' => 'cash',
        ]);
        $inRangeOrder->forceFill(['created_at' => '2026-06-15 10:00:00', 'updated_at' => '2026-06-15 10:00:00'])->save();

        SalesItem::create([
            'sales_order_id' => $inRangeOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 100,
            'tax_percentage' => 0,
        ]);

        $outOfRangeOrder = SalesOrder::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'RANGE-OLD',
            'subtotal' => 500,
            'tax_amount' => 0,
            'total_amount' => 500,
            'paid_amount' => 500,
            'payment_method' => 'cash',
        ]);
        $outOfRangeOrder->forceFill(['created_at' => '2026-05-15 10:00:00', 'updated_at' => '2026-05-15 10:00:00'])->save();

        SalesItem::create([
            'sales_order_id' => $outOfRangeOrder->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'selling_price' => 100,
            'tax_percentage' => 0,
        ]);

        $otherTenantOrder = SalesOrder::create([
            'tenant_id' => $otherTenant->id,
            'invoice_number' => 'RANGE-OTHER',
            'subtotal' => 700,
            'tax_amount' => 0,
            'total_amount' => 700,
            'paid_amount' => 700,
            'payment_method' => 'cash',
        ]);
        $otherTenantOrder->forceFill(['created_at' => '2026-06-15 10:00:00', 'updated_at' => '2026-06-15 10:00:00'])->save();

        SalesItem::create([
            'sales_order_id' => $otherTenantOrder->id,
            'product_id' => $otherProduct->id,
            'quantity' => 7,
            'selling_price' => 100,
            'tax_percentage' => 0,
        ]);

        PurchaseOrder::create([
            'tenant_id' => $tenant->id,
            'order_number' => 'PO-RANGE-001',
            'status' => 'received',
            'total_amount' => 80,
            'received_at' => '2026-06-15 10:00:00',
            'created_at' => '2026-06-15 10:00:00',
            'updated_at' => '2026-06-15 10:00:00',
        ]);

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'type' => 'sales_return',
            'quantity' => 1,
            'stock_after' => 10,
            'notes' => 'Range return',
            'user_id' => $owner->id,
            'created_at' => '2026-06-15 10:00:00',
            'updated_at' => '2026-06-15 10:00:00',
        ]);

        $response = $this->actingAs($owner)->get(route('reports.index', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-06-01"', false);
        $response->assertSee('value="2026-06-30"', false);
        $response->assertSee('&#8377;200', false);
        $response->assertSee('1 invoices');
        $response->assertSee('&#8377;120', false);
        $response->assertSee('&#8377;80', false);
        $response->assertSee('Range Report Product');
        $response->assertSee('2 sold');
        $response->assertSee('Range return');
        $response->assertDontSee('&#8377;500', false);
        $response->assertDontSee('Other Tenant Report Product');
    }

    public function test_purchase_history_search_paginates_and_preserves_query_string(): void
    {
        [$owner, $tenant] = $this->tenantOwner();
        [, $otherTenant] = $this->tenantOwner('other-purchase@example.com');

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Primary Purchase Supplier',
        ]);

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Filtered Purchase Product',
            'sku' => 'PO-FILTER',
        ]));

        foreach (range(1, 12) as $number) {
            $order = PurchaseOrder::create([
                'tenant_id' => $tenant->id,
                'supplier_id' => $supplier->id,
                'order_number' => sprintf('PO-FILTER-%02d', $number),
                'status' => 'received',
                'total_amount' => 100,
                'received_at' => now(),
            ]);

            PurchaseItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'purchase_price' => 100,
                'tax_percentage' => 18,
            ]);
        }

        PurchaseOrder::create([
            'tenant_id' => $otherTenant->id,
            'order_number' => 'PO-FILTER-999',
            'status' => 'received',
            'total_amount' => 100,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($owner)->get(route('purchases.index', [
            'per_page' => 10,
            'search' => 'PO-FILTER',
            'page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('data-purchase-search-form', false);
        $response->assertSee('data-purchase-search', false);
        $response->assertSee('10 / page');
        $response->assertSee('Showing 11-12 of 12 purchase orders');
        $response->assertSee('per_page=10', false);
        $response->assertSee('search=PO-FILTER', false);
        $response->assertSee('PO-FILTER-11');
        $response->assertDontSee('PO-FILTER-999');
    }

    public function test_purchase_form_defaults_price_and_tax_to_zero(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)->get(route('purchases.index'));

        $response->assertOk();
        $response->assertSee('name="purchase_price" min="0" step="0.01" value="0"', false);
        $response->assertSee('name="tax_percentage" min="0" max="99.99" step="0.01" value="0"', false);
    }

    public function test_purchase_form_shows_validation_hooks_and_custom_errors(): void
    {
        [$owner] = $this->tenantOwner();

        $response = $this->actingAs($owner)
            ->from(route('purchases.index'))
            ->post(route('purchases.store'), [
                'product_id' => null,
                'quantity' => 0,
                'purchase_price' => null,
                'tax_percentage' => 120,
            ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasErrors(['product_id', 'quantity', 'purchase_price', 'tax_percentage']);

        $followUp = $this->actingAs($owner)->get(route('purchases.index'));

        $followUp->assertOk();
        $followUp->assertSee('data-purchase-form', false);
        $followUp->assertSee('data-purchase-submit', false);
        $followUp->assertSee('Check the purchase details');
        $followUp->assertSee('Select a product to receive.');
        $followUp->assertSee('Quantity must be at least 1.');
        $followUp->assertSee('Enter the purchase price.');
        $followUp->assertSee('Tax percentage cannot exceed 99.99.');
    }

    public function test_purchase_store_rejects_product_from_another_tenant(): void
    {
        [$owner] = $this->tenantOwner();
        [$otherOwner, $otherTenant] = $this->tenantOwner('blocked-purchase-product@example.com');

        $product = Product::create($this->productPayload([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherOwner->id,
            'name' => 'Blocked Purchase Product',
            'sku' => 'BLOCKED-PO',
        ]));

        $response = $this->actingAs($owner)
            ->from(route('purchases.index'))
            ->post(route('purchases.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'purchase_price' => 50,
                'tax_percentage' => 18,
            ]);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHasErrors('product_id');
        $this->assertSame(0, PurchaseOrder::where('tenant_id', $owner->tenant_id)->count());
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
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $tenant->id,
            'type' => 'product_deleted',
            'title' => 'Product deleted',
        ]);
    }

    public function test_important_operations_create_activity_notifications(): void
    {
        [$owner, $tenant] = $this->tenantOwner();

        $product = Product::create($this->productPayload([
            'tenant_id' => $tenant->id,
            'user_id' => $owner->id,
            'name' => 'Notification Product',
            'sku' => 'NOTIFY-01',
            'inventory' => 20,
        ]));

        $this->actingAs($owner)
            ->from(route('suppliers.index'))
            ->post(route('suppliers.store'), [
                'name' => 'Notification Supplier',
                'contact_information' => 'supplier@example.com',
                'gst_number' => null,
                'payment_terms' => 'Net 7',
                'outstanding_balance' => 0,
            ])
            ->assertRedirect(route('suppliers.index'));

        $customerResponse = $this->actingAs($owner)
            ->from(route('customers.index'))
            ->post(route('customers.store'), [
                'name' => 'Notification Customer',
                'mobile' => '9000000000',
                'credit_limit' => 1000,
                'outstanding_balance' => 0,
            ]);

        $customerResponse->assertRedirect(route('customers.index'));
        $customer = Customer::where('tenant_id', $tenant->id)->where('name', 'Notification Customer')->firstOrFail();

        $this->actingAs($owner)
            ->from(route('purchases.index'))
            ->post(route('purchases.store'), [
                'product_id' => $product->id,
                'quantity' => 5,
                'purchase_price' => 40,
                'tax_percentage' => 18,
            ])
            ->assertRedirect(route('purchases.index'));

        $this->actingAs($owner)
            ->from(route('sales.index'))
            ->post(route('sales.store'), [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => 2,
                'paid_amount' => 236,
                'payment_method' => 'cash',
            ])
            ->assertRedirect(route('sales.index'));

        $this->actingAs($owner)
            ->from(route('returns.index'))
            ->post(route('returns.store'), [
                'product_id' => $product->id,
                'return_type' => 'sales_return',
                'quantity' => 1,
                'notes' => 'Returned by customer.',
            ])
            ->assertRedirect(route('returns.index'));

        foreach (['supplier_created', 'customer_created', 'purchase_received', 'sale_billed', 'return_processed'] as $type) {
            $this->assertDatabaseHas('notifications', [
                'tenant_id' => $tenant->id,
                'type' => $type,
            ]);
        }
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
