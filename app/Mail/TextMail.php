<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Plain-text transactional email rendered from resources/views/emails/*.blade.php (queued). */
class TextMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 4;

    public array $backoff = [30, 120, 600];

    public function __construct(public string $mailSubject, public string $template, public array $data = [], public array $replyToAddresses = []) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            replyTo: array_map(fn ($email) => new Address($email), array_filter($this->replyToAddresses)),
        );
    }

    public function content(): Content
    {
        return new Content(text: "emails.{$this->template}", with: $this->data);
    }
}
