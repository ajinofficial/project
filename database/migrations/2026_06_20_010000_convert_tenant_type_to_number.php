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

        $column = DB::selectOne(
            'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['tenants', 'tenant_type']
        );

        if ($column && ! in_array($column->DATA_TYPE, ['tinyint', 'smallint', 'int', 'bigint'], true)) {
            DB::table('tenants')->where('tenant_type', 'vendor')->update(['tenant_type' => 1]);
            DB::table('tenants')->where('tenant_type', 'client')->update(['tenant_type' => 2]);
            DB::table('tenants')->whereNull('tenant_type')->update(['tenant_type' => 2]);
            DB::statement('ALTER TABLE tenants MODIFY tenant_type TINYINT UNSIGNED NOT NULL DEFAULT 2');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tenants', 'tenant_type')) {
            return;
        }

        DB::statement('ALTER TABLE tenants MODIFY tenant_type VARCHAR(20) NOT NULL DEFAULT "client"');
        DB::table('tenants')->where('tenant_type', '1')->update(['tenant_type' => 'vendor']);
        DB::table('tenants')->where('tenant_type', '2')->update(['tenant_type' => 'client']);
    }
};
