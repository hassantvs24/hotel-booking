<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "We have paid you" — sent to the vendor when the admin marks a payout statement as paid. Built by PayoutService::mailData(). */
class PayoutPaidMail extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your Oystay payout has been sent — {$this->data['number']}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payout-paid', text: 'emails.payout-paid-text', with: ['d' => $this->data]);
    }
}
