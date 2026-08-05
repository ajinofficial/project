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
        public string $eventType,
        public string $businessName
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->businessName.' - '.$this->eventTitle)
            ->view('emails.important-event');
    }
}
