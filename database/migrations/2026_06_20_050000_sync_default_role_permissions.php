<?php

use App\Support\RolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenants')->orderBy('id')->get()->each(function ($tenant) {
            $current = json_decode($tenant->role_permissions ?? '[]', true);
            DB::table('tenants')->where('id', $tenant->id)->update([
                'role_permissions' => json_encode(RolePermission::normalize(is_array($current) ? $current : [])),
            ]);
        });
    }

    public function down(): void
    {
        //
    }
};
