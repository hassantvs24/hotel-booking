<?php

namespace App\Services\Notify;

/**
 * The kinds of notification a vendor (or the admin) can get, how each looks in the bell, and what each setting
 * of the notification preferences page switches. Every notification belongs to exactly one category.
 */
final class NotificationCategories
{
    /**
     * category => what the preferences page calls it and how its bell line looks by default.
     * "label" and "hint" are what a hotel's owner or staff read; "admin_label" / "admin_hint" are what the platform
     * admin reads (the admin gets these for every hotel, and a few of them are about different things).
     */
    public const ALL = [
        'messages'  => ['label' => 'Messages',          'hint' => 'A guest writes to you',                              'admin_hint' => 'A guest writes to any hotel',
                        'icon' => 'bx-message-dots', 'color' => 'teal'],
        'bookings'  => ['label' => 'Bookings',          'hint' => 'A new booking, or one that was cancelled',           'admin_hint' => 'A new booking, or one that was cancelled, at any hotel',
                        'icon' => 'bx-calendar-check', 'color' => 'blue'],
        'payments'  => ['label' => 'Payments',          'hint' => 'Money received for a booking',                       'admin_hint' => 'Money received for a booking, at any hotel',
                        'icon' => 'bx-credit-card', 'color' => 'green'],
        'offers'    => ['label' => 'Offers',            'hint' => 'A guest\'s offer, an answer, an offer about to run out', 'admin_hint' => 'A guest\'s offer, an answer, an offer about to run out, at any hotel',
                        'icon' => 'bx-tag', 'color' => 'amber'],
        'reviews'   => ['label' => 'Reviews',           'hint' => 'A guest reviewed your property',                     'admin_hint' => 'A guest reviewed any hotel',
                        'icon' => 'bx-star', 'color' => 'amber'],
        'refunds'   => ['label' => 'Refunds',           'hint' => 'A refund was asked for, approved, declined or paid', 'admin_hint' => 'A guest asks for a refund',
                        'icon' => 'bx-undo', 'color' => 'red'],
        'payouts'   => ['label' => 'Payouts',           'hint' => 'Money was sent to you',                              'admin_hint' => 'A vendor changed the account their payouts go to',
                        'icon' => 'bx-wallet', 'color' => 'green'],
        'property'  => ['label' => 'Your property',     'hint' => 'Approvals, name changes, photos hidden, documents',  'admin_label' => 'Properties', 'admin_hint' => 'A new property to approve, a name change to decide, a guest\'s report',
                        'icon' => 'bx-buildings', 'color' => 'teal'],
        'reminders' => ['label' => 'Reminders',         'hint' => 'Guests arriving tomorrow',                           'admin_hint' => 'Once a day: how many guests arrive tomorrow, across all hotels',
                        'icon' => 'bx-bell', 'color' => 'blue'],
    ];

    /** What the preferences page shows for one kind: the owner's wording, or the platform admin's */
    public static function wording(string $category, bool $forAdmin): array
    {
        $kind = self::ALL[$category];

        return $forAdmin
            ? ['label' => $kind['admin_label'] ?? $kind['label'], 'hint' => $kind['admin_hint']]
            : ['label' => $kind['label'], 'hint' => $kind['hint']];
    }

    /** the notification "type" words used so far (the old ones included) → category */
    private const TYPES = [
        'message' => 'messages',
        'booking' => 'bookings', 'booking_confirmed_owner' => 'bookings', 'booking_confirmed_guest' => 'bookings',
        'payment' => 'payments',
        'bid' => 'offers', 'bid_received' => 'offers', 'bid_accepted' => 'offers', 'bid_countered' => 'offers', 'bid_declined' => 'offers',
        'bid_withdrawn' => 'offers', 'bid_expired' => 'offers', 'bid_update' => 'offers', 'counter_accepted' => 'offers',
        'review' => 'reviews',
        'refund' => 'refunds',
        'payout' => 'payouts',
        'property' => 'property', 'property_published' => 'property', 'property_approved' => 'property', 'property_rejected' => 'property',
        'property_documents' => 'property',
        'reminder' => 'reminders',
    ];

    /** The category of a notification: the one it says, else the one its type belongs to, else the nearest guess */
    public static function of(?string $type, ?string $explicit = null): string
    {
        if ($explicit && isset(self::ALL[$explicit])) {
            return $explicit;
        }

        $type = (string) $type;

        return self::TYPES[$type] ?? (str_starts_with($type, 'bid') ? 'offers' : (str_starts_with($type, 'property') ? 'property' : (str_starts_with($type, 'booking') ? 'bookings' : 'property')));
    }

    /** What every vendor starts with: a toast for everything, no sound, no email */
    public static function defaults(): array
    {
        return array_map(fn () => ['toast' => true, 'sound' => false, 'email' => false], self::ALL);
    }

    public static function keys(): array
    {
        return array_keys(self::ALL);
    }
}
