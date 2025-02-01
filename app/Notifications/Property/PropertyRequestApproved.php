<?php

namespace App\Notifications\Property;

use App\Services\MailService;

class PropertyRequestApproved
{
    public static function send($recipient, $data = []): bool
    {
        try {
            return MailService::sendMail(
                $recipient,
                'Property Request Approved',
                'mail.property.request-approved',
                $data
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
