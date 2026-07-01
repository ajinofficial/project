<?php

namespace App\Support;

use App\Models\Notification;

class ActivityNotifier
{
    public static function notify(int|string|null $tenantId, string $type, string $title, string $message): void
    {
        if (! $tenantId) {
            return;
        }

        Notification::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'message' => $message,
        ]);
    }
}
