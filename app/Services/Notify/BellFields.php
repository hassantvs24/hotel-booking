<?php

namespace App\Services\Notify;

/**
 * The older notifications (bids, payments, property status) were written for the guest site: they have a "body" and a
 * "url" for the guest pages. The admin panel's bell wants "message", "category", "icon" and "color" as well.
 * This adds them, and leaves everything that is already there untouched.
 */
final class BellFields
{
    public static function add(array $content): array
    {
        $category = NotificationCategories::of($content['type'] ?? null, $content['category'] ?? null);
        $look = NotificationCategories::ALL[$category];

        return array_merge([
            'message'  => $content['body'] ?? '',
            'category' => $category,
            'icon'     => $look['icon'],
            'color'    => $look['color'],
        ], $content, ['category' => $category]);
    }
}
