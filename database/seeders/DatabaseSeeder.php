<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\RolePermission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::table('plans')->updateOrInsert(
            ['id' => 4],
            [
                'name' => 'free_trial',
                'monthly_price' => 0,
                'features' => '30 days, 1 store, 1 user',
                'store_limit' => 1,
                'user_limit' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $plan = Plan::firstOrCreate(
            ['name' => 'starter'],
            [
                'monthly_price' => 499,
                'features' => '1 store, 2 users',
                'store_limit' => 1,
                'user_limit' => 2,
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['email' => 'admin@stockpilot.test'],
            [
                'plan_id' => $plan->id,
                'tenant_type' => Tenant::TYPE_VENDOR,
                'business_name' => 'StockPilot Demo Store',
                'owner_name' => 'Store Admin',
                'mobile' => '+91 98765 43210',
                'business_category' => Tenant::CATEGORY_RETAIL,
                'store_address' => 'Demo market road, Bengaluru',
                'domain_expired_date' => now()->addYears(5)->toDateString(),
                'role_permissions' => RolePermission::defaults(),
            ]
        );

        $this->ensureOnlyFirstTenantIsVendor();

        $user = User::firstOrCreate(
            ['email' => 'admin@stockpilot.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Store Admin',
                'company_name' => 'StockPilot Demo Store',
                'store_url' => 'stockpilot-demo',
                'phone' => '+1 555 0199',
                'plan' => $plan->id,
                'role' => User::ROLE_OWNER,
                'password' => 'password',
            ]
        );

        $user->update(['tenant_id' => $tenant->id, 'plan' => $plan->id, 'role' => User::ROLE_OWNER]);

        $products = [
            [
                'name' => 'Cotton Crew T-Shirt',
                'sku' => 'APP-TS-001',
                'category' => 'Apparel',
                'brand' => 'CoreWear',
                'supplier' => 'Metro Supplies',
                'purchase_price' => 12.00,
                'price' => 24.00,
                'compare_at_price' => 32.00,
                'tax_percentage' => 18,
                'inventory' => 42,
                'minimum_stock_level' => 10,
                'status' => 'active',
                'description' => 'Core apparel item for front-of-store display.',
            ],
            [
                'name' => 'Organic Ground Coffee',
                'sku' => 'GRC-CF-014',
                'category' => 'Grocery',
                'brand' => 'Daily Roast',
                'supplier' => 'Metro Supplies',
                'purchase_price' => 8.25,
                'price' => 12.50,
                'compare_at_price' => null,
                'tax_percentage' => 5,
                'inventory' => 7,
                'minimum_stock_level' => 10,
                'status' => 'active',
                'description' => 'Low stock item. Reorder from supplier before weekend.',
            ],
            [
                'name' => 'Bluetooth Shelf Speaker',
                'sku' => 'ELC-SP-220',
                'category' => 'Electronics',
                'brand' => 'SoundMax',
                'supplier' => 'Gadget Wholesale',
                'purchase_price' => 52.00,
                'price' => 79.99,
                'compare_at_price' => 99.99,
                'tax_percentage' => 18,
                'inventory' => 0,
                'minimum_stock_level' => 5,
                'status' => 'active',
                'description' => 'Out of stock. Awaiting next supplier shipment.',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['user_id' => $user->id, 'sku' => $product['sku']],
                array_merge($product, ['tenant_id' => $tenant->id, 'user_id' => $user->id])
            );
        }
    }

    private function ensureOnlyFirstTenantIsVendor(): void
    {
        $vendorTenantId = Tenant::orderBy('id')->value('id');

        if (! $vendorTenantId) {
            return;
        }

        Tenant::where('id', $vendorTenantId)->update(['tenant_type' => Tenant::TYPE_VENDOR]);
        Tenant::where('id', '<>', $vendorTenantId)->update(['tenant_type' => Tenant::TYPE_CLIENT]);
    }
}
