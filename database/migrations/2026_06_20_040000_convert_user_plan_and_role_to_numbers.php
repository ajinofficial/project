<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'plan')) {
            $planColumn = DB::selectOne(
                'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['users', 'plan']
            );

            if ($planColumn && ! in_array($planColumn->DATA_TYPE, ['tinyint', 'smallint', 'int', 'bigint'], true)) {
                DB::table('plans')->pluck('id', 'name')->each(function ($id, $name) {
                    DB::table('users')->where('plan', $name)->update(['plan' => $id]);
                });

                $starterPlanId = DB::table('plans')->where('name', 'starter')->value('id') ?: 1;
                DB::statement("UPDATE users SET plan = {$starterPlanId} WHERE plan IS NULL OR plan = '' OR plan NOT REGEXP '^[0-9]+$'");
                DB::statement('ALTER TABLE users MODIFY plan BIGINT UNSIGNED NOT NULL DEFAULT 1');
            }
        }

        if (Schema::hasColumn('users', 'role')) {
            $roleColumn = DB::selectOne(
                'SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['users', 'role']
            );

            if ($roleColumn && ! in_array($roleColumn->DATA_TYPE, ['tinyint', 'smallint', 'int', 'bigint'], true)) {
                DB::table('users')->where('role', 'owner')->update(['role' => 1]);
                DB::table('users')->where('role', 'manager')->update(['role' => 2]);
                DB::table('users')->where('role', 'sales_staff')->update(['role' => 3]);
                DB::table('users')->where('role', 'warehouse_staff')->update(['role' => 4]);
                DB::table('users')->where('role', 'accountant')->update(['role' => 5]);
                DB::statement("UPDATE users SET role = 1 WHERE role IS NULL OR role = '' OR role NOT REGEXP '^[0-9]+$'");
                DB::statement('ALTER TABLE users MODIFY role TINYINT UNSIGNED NOT NULL DEFAULT 1');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'plan')) {
            DB::statement('ALTER TABLE users MODIFY plan VARCHAR(255) NOT NULL DEFAULT "starter"');
            DB::table('plans')->pluck('name', 'id')->each(function ($name, $id) {
                DB::table('users')->where('plan', (string) $id)->update(['plan' => $name]);
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            DB::statement('ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT "owner"');
            DB::table('users')->where('role', '1')->update(['role' => 'owner']);
            DB::table('users')->where('role', '2')->update(['role' => 'manager']);
            DB::table('users')->where('role', '3')->update(['role' => 'sales_staff']);
            DB::table('users')->where('role', '4')->update(['role' => 'warehouse_staff']);
            DB::table('users')->where('role', '5')->update(['role' => 'accountant']);
        }
    }
};
