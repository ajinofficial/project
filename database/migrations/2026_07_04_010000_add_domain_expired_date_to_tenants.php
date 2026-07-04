<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'domain_expired_date')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->date('domain_expired_date')->nullable()->after('invoice_prefix');
            });
        }

        DB::table('tenants')
            ->leftJoin('plans', 'tenants.plan_id', '=', 'plans.id')
            ->whereNull('tenants.domain_expired_date')
            ->where(function ($query) {
                $query->where('plans.id', 4)->orWhere('plans.name', 'free_trial');
            })
            ->update(['tenants.domain_expired_date' => now()->addDays(30)->toDateString()]);

        DB::table('tenants')
            ->leftJoin('plans', 'tenants.plan_id', '=', 'plans.id')
            ->whereNull('tenants.domain_expired_date')
            ->where(function ($query) {
                $query->whereNull('plans.id')
                    ->orWhere('plans.id', '<>', 4)
                    ->where('plans.name', '<>', 'free_trial');
            })
            ->update(['tenants.domain_expired_date' => now()->addYears(5)->toDateString()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'domain_expired_date')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('domain_expired_date');
            });
        }
    }
};
