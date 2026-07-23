<?php

namespace App\Services;

use App\Models\EmailIntegration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class TenantEmailSender
{
    public function send(EmailIntegration $integration, string $recipient, string $subject, string $message): void
    {
        Config::set('mail.mailers.tenant_smtp', [
            'transport' => 'smtp',
            'host' => $integration->host,
            'port' => $integration->port,
            'encryption' => $integration->encryption ?: null,
            'username' => $integration->username,
            'password' => $integration->password,
            'timeout' => 15,
        ]);

        Mail::purge('tenant_smtp');
        Mail::mailer('tenant_smtp')->raw($message, function ($mail) use ($integration, $recipient, $subject) {
            $mail->from($integration->from_address, $integration->from_name)
                ->to($recipient)
                ->subject($subject);
        });
    }
}
