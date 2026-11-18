<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtp extends Notification
{
    use Queueable;

    public function __construct(private readonly string $otp)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $businessName = $notifiable->tenant?->business_name
            ?: $notifiable->company_name
            ?: config('app.name');

        return (new MailMessage)
            ->subject($businessName.' - '.__('auth.verify_email_subject'))
            ->greeting(__('auth.greeting', ['name' => $notifiable->name]))
            ->line(__('auth.verify_email_instructions'))
            ->line($this->otp)
            ->line(__('auth.this_otp_expires_in_10_minutes_do_not_share_it_with_anyone_6f2220'));
    }
}
