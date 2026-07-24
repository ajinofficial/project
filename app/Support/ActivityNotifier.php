<?php

namespace App\Support;

use App\Mail\ImportantEventMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ActivityNotifier
{
    public static function notify(
        int|string|null $tenantId,
        string $type,
        string $title,
        string $message,
        bool $sendEmail = true
    ): void
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

        if (! $sendEmail) {
            return;
        }

        $recipients = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', User::ROLE_OWNER)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recipients === []) {
            return;
        }

        try {
            Mail::to($recipients)->send(new ImportantEventMail($title, $message, $type));
        } catch (Throwable $exception) {
            Log::error('Important event email notification could not be sent.', [
                'tenant_id' => $tenantId,
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }
}
