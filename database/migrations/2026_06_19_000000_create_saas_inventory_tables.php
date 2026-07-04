<?php

use Illuminate\Database\Migrations\Migration;
use App\Support\RolePermission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('monthly_price');
            $table->string('features');
            $table->unsignedInteger('store_limit')->default(1);
            $table->unsignedInteger('user_limit')->nullable();
            $table->timestamps();
        });

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('business_name');
            $table->string('owner_name');
            $table->string('mobile', 30);
            $table->string('email');
            $table->string('gst_number')->nullable();
            $table->unsignedTinyInteger('business_category')->default(1);
            $table->text('store_address');
            $table->string('currency', 8)->default('INR');
            $table->decimal('default_tax_percentage', 5, 2)->default(18);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->string('invoice_prefix')->default('INV');
            $table->date('domain_expired_date')->nullable();
            $table->json('role_permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('role')->default(1)->after('plan');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('barcode')->nullable()->after('sku');
            $table->string('brand')->nullable()->after('category');
            $table->string('supplier')->nullable()->after('brand');
            $table->decimal('purchase_price', 12, 2)->default(0)->after('supplier');
            $table->decimal('tax_percentage', 5, 2)->default(18)->after('compare_at_price');
            $table->unsignedInteger('minimum_stock_level')->default(10)->after('inventory');
            $table->unsignedInteger('reserved_stock')->default(0)->after('minimum_stock_level');
            $table->unsignedInteger('damaged_stock')->default(0)->after('reserved_stock');
            $table->unsignedInteger('returned_stock')->default(0)->after('damaged_stock');

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('permissions');
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contact_information')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('payment_terms')->nullable();
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 30)->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('status')->default('received');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->timestamps();
        });

        Schema::create('sales_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('selling_price', 12, 2);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->integer('quantity');
            $table->unsignedInteger('stock_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('cash');
            $table->string('status')->default('paid');
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->date('renews_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('channel')->default('in_app');
            $table->string('title');
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->seedPlans();
        $this->migrateExistingUsers();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sales_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('roles');

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn([
                'barcode',
                'brand',
                'supplier',
                'purchase_price',
                'tax_percentage',
                'minimum_stock_level',
                'reserved_stock',
                'damaged_stock',
                'returned_stock',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('tenants');
        Schema::dropIfExists('plans');
    }

    private function seedPlans(): void
    {
        DB::table('plans')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'starter',
                'monthly_price' => 499,
                'features' => '1 store, 2 users',
                'store_limit' => 1,
                'user_limit' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'growth',
                'monthly_price' => 999,
                'features' => '5 users, advanced reports',
                'store_limit' => 1,
                'user_limit' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'premium',
                'monthly_price' => 1999,
                'features' => 'Unlimited users, multi-store',
                'store_limit' => 99,
                'user_limit' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'free_trial',
                'monthly_price' => 0,
                'features' => '30 days, 1 store, 1 user',
                'store_limit' => 1,
                'user_limit' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function migrateExistingUsers(): void
    {
        $starterPlanId = DB::table('plans')->where('name', 'starter')->value('id');

        DB::table('users')->orderBy('id')->get()->each(function ($user) use ($starterPlanId) {
            $tenantId = DB::table('tenants')->insertGetId([
                'plan_id' => $starterPlanId,
                'business_name' => $user->company_name ?: $user->name."'s Store",
                'owner_name' => $user->name,
                'mobile' => $user->phone ?: '0000000000',
                'email' => $user->email,
                'business_category' => 1,
                'store_address' => 'Not configured',
                'domain_expired_date' => now()->addYears(5)->toDateString(),
                'role_permissions' => json_encode(RolePermission::defaults()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update(['tenant_id' => $tenantId, 'plan' => $starterPlanId, 'role' => 1]);
            DB::table('products')->where('user_id', $user->id)->update(['tenant_id' => $tenantId]);
        });
    }
};
