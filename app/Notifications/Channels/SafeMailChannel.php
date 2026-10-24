<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The email way of an admin-panel notification. A notification is sent from the middle of other work (a booking, a
 * payment, a refund), so an email that cannot be sent (the mail server is down, the limit is reached) must never
 * stop that work: it is written to the log and everything else carries on.
 */
class SafeMailChannel
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        try {
            return app(MailChannel::class)->send($notifiable, $notification);
        } catch (Throwable $e) {
            Log::warning('Notification email was not sent: ' . $e->getMessage());

            return null;
        }
    }
}
