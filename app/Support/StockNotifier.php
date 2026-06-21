<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\Product;

class StockNotifier
{
    public static function sync(Product $product): void
    {
        if (! $product->tenant_id || $product->status === 'archived') {
            return;
        }

        if ($product->inventory <= 0) {
            self::create($product, 'stock_out', 'Out of stock', $product->name.' has no stock available.');

            return;
        }

        if ($product->inventory <= $product->minimum_stock_level) {
            self::create($product, 'stock_low', 'Low stock alert', $product->name.' is down to '.$product->inventory.' units.');
        }
    }

    private static function create(Product $product, string $type, string $title, string $message): void
    {
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
    }
}
