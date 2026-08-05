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
            ->subject($businessName.' - Verify your email address')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this six-digit OTP to verify your email address:')
            ->line($this->otp)
            ->line('This OTP expires in 10 minutes. Do not share it with anyone.');
    }
}
