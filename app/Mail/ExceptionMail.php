<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExceptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $userinfo, public string $content, public string $url, public string $css) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: config('admin.email'),
            subject: config('app.name').' - Exception',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.exception',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
