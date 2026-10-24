<?php

namespace App\Notifications\Admin;

use App\Models\User;
use App\Notifications\Channels\SafeMailChannel;
use App\Services\Notify\NotificationCategories;
use App\Services\Notify\NotificationPreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
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
    ) {
        $this->kept = new \ArrayObject();
    }

    /**
     * Every notification is kept for the bell and sent live to the person's own channel; an email goes too
     * when they asked for one for this kind of notification.
     */
    public function via(object $notifiable): array
    {
        $via = ['database', 'broadcast'];

        if ($this->wantsEmail($notifiable)) {
            $via[] = SafeMailChannel::class;
        }

        return $via;
    }

    /** The email is only a nicety: if even looking up the person's choice fails, no email goes, and the work carries on */
    private function wantsEmail(object $notifiable): bool
    {
        try {
            return $notifiable instanceof User && app(NotificationPreferences::class)->wants($notifiable, $this->category(), 'email');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function category(): string
    {
        return NotificationCategories::of($this->type, $this->extra['category'] ?? null);
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    /** The live message keeps the word the notification was made with ("message", "bid_received"...) as its type */
    public function broadcastType(): string
    {
        return $this->type;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->message);
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
        // Laravel asks once per way of sending, and the bell line saved first would make every later way see "a repeat":
        // so the question is asked once (at the bell list) and the live message and the email follow the same answer.
        $key = method_exists($notifiable, 'getKey') ? $notifiable->getKey() : spl_object_id($notifiable);

        if ($channel === 'database' || !isset($this->kept[$key])) {
            $this->kept[$key] = $this->isNew($notifiable);
        }

        return $this->kept[$key];
    }

    /**
     * per person: was this notification new, or a repeat of one they already have.
     * (An object, because Laravel sends each way from its own clone of the notification and a clone still shares it.)
     */
    private \ArrayObject $kept;

    private function isNew(object $notifiable): bool
    {
        // a notification that is a new event every time (a report, a name request) is never taken for a repeat
        if (!empty($this->extra['always'])) {
            return true;
        }

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

            if (!empty($this->extra['refund_request_id']) && (string)($data['refund_request_id'] ?? '') === (string)$this->extra['refund_request_id']) {
                return false;
            }

            // Fallback: same title+message within 60 seconds
            if (
                empty($this->extra['group_ref']) &&
                empty($this->extra['booking_number']) &&
                empty($this->extra['room_request_id']) &&
                empty($this->extra['refund_request_id']) &&
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
            'category'   => $this->category(),
            'title'      => $this->title,
            'message'    => $this->message,
            'icon'       => $this->icon,
            'color'      => $this->color,
            'created_at' => now()->toISOString(),
        ], $this->extra);
    }
}
