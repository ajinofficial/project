<?php

use App\Models\Notification;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('status', '<>', 'archived')
            ->where(function ($query) {
                $query->where('inventory', '<=', 0)
                    ->orWhereColumn('inventory', '<=', 'minimum_stock_level');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($product) {
                $type = $product->inventory <= 0 ? 'stock_out' : 'stock_low';
                $title = $product->inventory <= 0 ? 'Out of stock' : 'Low stock alert';
                $message = $product->inventory <= 0
                    ? $product->name.' has no stock available.'
                    : $product->name.' is down to '.$product->inventory.' units.';

                Notification::firstOrCreate(
                    [
                        'tenant_id' => $product->tenant_id,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'read_at' => null,
                    ],
                    ['channel' => 'in_app']
                );
            });
    }

    public function down(): void
    {
        DB::table('notifications')->whereIn('type', ['stock_out', 'stock_low'])->delete();
    }
};
