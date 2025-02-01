<?php

namespace App\Notifications\Property;

use App\Services\MailService;

class PropertyRequestRejected
{
    public static function send($recipient, $data = []): bool
    {
        try {
            return MailService::sendMail(
                $recipient,
                'Property Request Rejected',
                'mail.property.request-rejected',
                $data
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
