<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAccountSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $setupUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Set Up Your DORA Tenant Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'email.tenant-account-setup',
        );
    }
}
