<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedTinyInteger('tenant_type')->default(2)->after('plan_id');
            $table->index('tenant_type');
        });

        $vendorTenantId = DB::table('tenants')
            ->where('email', 'admin@stockpilot.test')
            ->value('id') ?: DB::table('tenants')->orderBy('id')->value('id');

        if ($vendorTenantId) {
            DB::table('tenants')->where('id', $vendorTenantId)->update(['tenant_type' => 1]);
            DB::table('tenants')->where('id', '<>', $vendorTenantId)->update(['tenant_type' => 2]);
        }
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['tenant_type']);
            $table->dropColumn('tenant_type');
        });
    }
};
