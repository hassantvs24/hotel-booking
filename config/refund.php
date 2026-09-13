<?php
return [
    'tiers' => [
        [
            'minimum_hours_before_checkin' => 168,
            'refund_percentage' => 100,
            'label' => 'Free cancellation',
        ],
        [
            'minimum_hours_before_checkin' => 48,
            'refund_percentage' => 80,
            'label' => '20% cancellation fee',
        ],
        [
            'minimum_hours_before_checkin' => 1,
            'refund_percentage' => 50,
            'label' => '50% cancellation fee',
        ],
    ],

    'currency' => 'BDT',

    'reasons' => [
        'change_of_plans' => 'Change of travel plans',
        'medical_emergency' => 'Medical emergency',
        'booking_mistake' => 'Booking made by mistake',
        'property_issue' => 'Property-related problem',
        'duplicate_payment' => 'Duplicate payment',
        'other' => 'Other',
    ],
];
