<?php

namespace App\Services\Notify;

use Illuminate\Notifications\DatabaseNotification;

/** One notification the way the admin panel's bell wants it, whichever class made it and however old it is. */
final class NotificationPresenter
{
    public static function categoryOf(DatabaseNotification $n): string
    {
        $d = (array) $n->data;

        return NotificationCategories::of($d['type'] ?? null, $d['category'] ?? null);
    }

    public static function present(DatabaseNotification $n): array
    {
        $d = (array) $n->data;
        $category = self::categoryOf($n);
        $look = NotificationCategories::ALL[$category];

        return [
            'id'              => $n->id,
            'type'            => $d['type'] ?? 'info',
            'category'        => $category,
            'title'           => $d['title'] ?? 'Notification',
            'message'         => $d['message'] ?? $d['body'] ?? '',
            'icon'            => $d['icon'] ?? $look['icon'],
            'color'           => $d['color'] ?? $look['color'],
            'read'            => !is_null($n->read_at),
            'created_at'      => $n->created_at->toISOString(),
            'conversation_id' => $d['conversation_id'] ?? null,
            'property_id'     => $d['property_id'] ?? null,
            'booking_number'  => $d['booking_number'] ?? null,
            'count'           => $d['count'] ?? null,
        ];
    }
}
