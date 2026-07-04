<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PLAN_ID = 4;

    public function up(): void
    {
        DB::table('plans')->updateOrInsert(
            ['id' => self::PLAN_ID],
            [
                'name' => 'free_trial',
                'monthly_price' => 0,
                'features' => '30 days, 1 store, 1 user',
                'store_limit' => 1,
                'user_limit' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('plans')->where('id', self::PLAN_ID)->where('name', 'free_trial')->delete();
    }
};
