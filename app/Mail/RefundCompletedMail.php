<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Your refund is complete" — built from RefundNotificationMailer::build() */
class RefundCompletedMail extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        $headline = ($this->data['stage'] ?? 'completed') === 'processing' ? 'Your refund is on its way' : 'Your refund is complete';

        return new Envelope(
            subject: "{$headline} — {$this->data['hotel']} · #{$this->data['refund_number']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.refund-completed',
            text: 'emails.refund-completed-text',
            with: ['d' => $this->data],
        );
    }
}
