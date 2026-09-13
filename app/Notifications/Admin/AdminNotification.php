<?php

namespace App\Notifications\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $type,
        private string $title,
        private string $message,
        private string $icon    = 'bx-bell',
        private string $color   = 'teal',
        private array  $extra   = [],
        private string $channel = 'admin',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Prevent duplicates using PHP-side comparison instead of MySQL JSON functions.
     * More reliable across MySQL/MariaDB versions.
     *
     * Dedup priority:
     * 1. group_ref      → group booking, permanent
     * 2. booking_number → single booking, permanent
     * 3. room_request_id → bid, permanent
     * 4. title+message  → anything else, 60s window
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        $since = now()->subHour();

        $recent = $notifiable->notifications()
            ->where('type', static::class)
            ->where('created_at', '>=', $since)
            ->get(['data']);

        foreach ($recent as $n) {
            $data = $n->data;

            if (!empty($this->extra['group_ref']) && ($data['group_ref'] ?? null) === $this->extra['group_ref']) {
                return false;
            }

            if (!empty($this->extra['booking_number']) && (string)($data['booking_number'] ?? '') === (string)$this->extra['booking_number']) {
                return false;
            }

            if (!empty($this->extra['room_request_id']) && (string)($data['room_request_id'] ?? '') === (string)$this->extra['room_request_id']) {
                return false;
            }

            // Fallback: same title+message within 60 seconds
            if (
                empty($this->extra['group_ref']) &&
                empty($this->extra['booking_number']) &&
                empty($this->extra['room_request_id']) &&
                ($data['title'] ?? '') === $this->title &&
                ($data['message'] ?? '') === $this->message
            ) {
                return false;
            }
        }

        return true;
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge([
            'type'       => $this->type,
            'title'      => $this->title,
            'message'    => $this->message,
            'icon'       => $this->icon,
            'color'      => $this->color,
            'created_at' => now()->toISOString(),
        ], $this->extra);
    }
}
