<?php

use App\Support\RolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->update([
            'role_permissions' => json_encode(RolePermission::defaults()),
        ]);
    }

    public function down(): void
    {
        //
    }
};
