<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'business_category')) {
            return;
        }

        $column = DB::selectOne(
            'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['tenants', 'business_category']
        );

        if ($column && ! in_array($column->DATA_TYPE, ['tinyint', 'smallint', 'int', 'bigint'], true)) {
            DB::table('tenants')->whereIn('business_category', ['Retail', 'retail'])->update(['business_category' => 1]);
            DB::table('tenants')->whereIn('business_category', ['Mobile', 'mobile'])->update(['business_category' => 2]);
            DB::table('tenants')->whereIn('business_category', ['Pharmacy', 'pharmacy'])->update(['business_category' => 3]);
            DB::table('tenants')->whereIn('business_category', ['Hardware', 'hardware'])->update(['business_category' => 4]);
            DB::table('tenants')->whereIn('business_category', ['Grocery', 'grocery'])->update(['business_category' => 5]);
            DB::table('tenants')->whereIn('business_category', ['Apparel', 'apparel'])->update(['business_category' => 6]);
            DB::table('tenants')->whereIn('business_category', ['Electronics', 'electronics'])->update(['business_category' => 7]);
            DB::table('tenants')->whereIn('business_category', ['Restaurant', 'restaurant'])->update(['business_category' => 8]);
            DB::table('tenants')->whereNull('business_category')->orWhere('business_category', '')->update(['business_category' => 9]);
            DB::statement("UPDATE tenants SET business_category = 9 WHERE business_category NOT REGEXP '^[0-9]+$'");
            DB::statement('ALTER TABLE tenants MODIFY business_category TINYINT UNSIGNED NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tenants', 'business_category')) {
            return;
        }

        DB::statement('ALTER TABLE tenants MODIFY business_category VARCHAR(120) NOT NULL DEFAULT "Retail"');
        DB::table('tenants')->where('business_category', '1')->update(['business_category' => 'Retail']);
        DB::table('tenants')->where('business_category', '2')->update(['business_category' => 'Mobile']);
        DB::table('tenants')->where('business_category', '3')->update(['business_category' => 'Pharmacy']);
        DB::table('tenants')->where('business_category', '4')->update(['business_category' => 'Hardware']);
        DB::table('tenants')->where('business_category', '5')->update(['business_category' => 'Grocery']);
        DB::table('tenants')->where('business_category', '6')->update(['business_category' => 'Apparel']);
        DB::table('tenants')->where('business_category', '7')->update(['business_category' => 'Electronics']);
        DB::table('tenants')->where('business_category', '8')->update(['business_category' => 'Restaurant']);
        DB::table('tenants')->where('business_category', '9')->update(['business_category' => 'Other']);
    }
};
