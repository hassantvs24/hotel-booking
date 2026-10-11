<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Your booking is confirmed" — built from BookingConfirmationMailer::build() */
class BookingConfirmedMail extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        $hotels = $this->data['hotel_names'];
        $where  = count($hotels) === 1 ? $hotels[0] : count($hotels) . ' stays';

        return new Envelope(
            subject: "Booking confirmed — {$where} · #{$this->data['reference']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
            text: 'emails.booking-confirmed-text',
            with: ['d' => $this->data],
        );
    }
}
