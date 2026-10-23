<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "You have a new message from <hotel>" — sent to a guest when the property writes to them. Built by GuestMessageMailer::build(). */
class GuestMessageMail extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You have a new message from {$this->data['sender']}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.guest-message', text: 'emails.guest-message-text', with: ['d' => $this->data]);
    }
}
