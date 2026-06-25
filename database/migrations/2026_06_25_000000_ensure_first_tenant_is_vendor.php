<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'tenant_type')) {
            return;
        }

        $vendorTenantId = DB::table('tenants')->orderBy('id')->value('id');

        if (! $vendorTenantId) {
            return;
        }

        DB::table('tenants')->where('id', $vendorTenantId)->update(['tenant_type' => 1]);
        DB::table('tenants')->where('id', '<>', $vendorTenantId)->update(['tenant_type' => 2]);
    }

    public function down(): void
    {
        //
    }
};
