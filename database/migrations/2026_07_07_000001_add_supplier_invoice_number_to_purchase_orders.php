<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('supplier_invoice_number')->nullable()->after('order_number');
            $table->unique(['tenant_id', 'supplier_invoice_number'], 'purchase_orders_tenant_supplier_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropUnique('purchase_orders_tenant_supplier_invoice_unique');
            $table->dropColumn('supplier_invoice_number');
        });
    }
};
