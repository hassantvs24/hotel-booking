<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Reset your Oystay password" — the link opens the reset page of the app the person asked from */
class PasswordResetMail extends Mailable
{
    public function __construct(public string $name, public string $url, public int $minutes, public bool $isAdminApp) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset your Oystay password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.password-reset');
    }
}
