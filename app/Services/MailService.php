<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
class MailService
{
    /**
     * Send an email using a specified view and data
     *
     * @param string $recipient
     * @param string $subject
     * @param string $view
     * @param array $data
     * @return bool
     */

    public static function sendMail(string $recipient, string $subject, string $view, array $data): bool
    {
        try {
            Mail::send($view, $data, function ($message) use ($recipient, $subject) {
                $message->to($recipient)->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            Log::error("Email failed to send: " . $e->getMessage());
            return false;
        }
    }
}
