<?php

namespace App\Notifications\Property;

use App\Services\MailService;

/** The email that says a property is live, with everything about it */
class PropertyPublished
{
    public static function send($recipient, $data = []): bool
    {
        try {
            return MailService::sendMail(
                $recipient,
                'Your property is now live on Oystay',
                'mail.property.property-published',
                $data
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
