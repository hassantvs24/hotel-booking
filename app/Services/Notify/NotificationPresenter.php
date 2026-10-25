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

    /** The ids a notification of the "property" kind can carry, and the page of the admin each belongs to */
    public const SUBKINDS = ['property_requests', 'name_requests', 'property_reports'];

    /** Which page of the admin a notification belongs to, when that is more exact than its kind (null: just its kind) */
    public static function subkindOf(array $d): ?string
    {
        return !empty($d['property_request_id']) ? 'property_requests'
            : (!empty($d['name_request_id']) ? 'name_requests'
            : (!empty($d['report_id']) ? 'property_reports' : null));
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
            'property_request_id' => $d['property_request_id'] ?? null,
            'name_request_id' => $d['name_request_id'] ?? null,
            'report_id'       => $d['report_id'] ?? null,
            'booking_number'  => $d['booking_number'] ?? null,
            'count'           => $d['count'] ?? null,
        ];
    }
}
