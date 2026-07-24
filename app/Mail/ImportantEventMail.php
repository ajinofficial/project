<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportantEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventTitle,
        public string $eventMessage,
        public string $eventType
    ) {}

    public function build(): self
    {
        return $this
            ->subject('[Investrivo] '.$this->eventTitle)
            ->view('emails.important-event');
    }
}
