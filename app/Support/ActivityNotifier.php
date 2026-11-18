<?php

namespace App\Support;

use App\Mail\ImportantEventMail;
use App\Models\Notification;
use App\Models\Tenant;
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
        bool $sendEmail = true,
        array $parameters = []
    ): void
    {
        if (! $tenantId) {
            return;
        }

        $translationData = $parameters !== [] ? ['key' => 'notifications.events.'.$type, 'parameters' => $parameters] : null;

        Notification::create([
            'tenant_id' => $tenantId,
            'type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'message' => $message,
            'translation_data' => $translationData,
        ]);

        if (! $sendEmail) {
            return;
        }

        $recipients = User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', User::ROLE_OWNER)
            ->whereNotNull('email')
            ->get(['email', 'preferred_locale']);

        if ($recipients->isEmpty()) {
            return;
        }

        $businessName = Tenant::query()->whereKey($tenantId)->value('business_name') ?: config('app.name');

        try {
            foreach ($recipients->unique('email') as $recipient) {
                $locale = LocalePreference::supported($recipient->preferred_locale) ? $recipient->preferred_locale : 'en';
                $recipientParameters = $parameters;
                foreach (['role', 'kind'] as $field) {
                    if (isset($recipientParameters[$field])) {
                        $recipientParameters[$field] = app('translator')->get(ucfirst($recipientParameters[$field]), [], $locale);
                    }
                }
                $translatedTitle = app('translator')->get($title, [], $locale);
                $translatedMessage = $translationData
                    ? app('translator')->get($translationData['key'], $recipientParameters, $locale)
                    : app('translator')->get($message, [], $locale);
                Mail::to($recipient->email)->locale($locale)->send(new ImportantEventMail($translatedTitle, $translatedMessage, $type, $businessName));
            }
        } catch (Throwable $exception) {
            Log::error('Important event email notification could not be sent.', [
                'tenant_id' => $tenantId,
                'type' => $type,
                'exception' => $exception,
            ]);
        }
    }
}
