<?php

return [
    'allowed_facility_for' => ['Room', 'Property'],

    'allowed_property_class' => [
        '7 Stars',
        '6 Stars',
        '5 Stars',
        '4 Stars',
        '3 Stars',
        '2 Stars',
        '1 Star',
        'Unrated'
    ],

    'allowed_property_status' => [
        'Pending',
        'Published',
        'Unpublished'
    ],

    'allowed_offer_for' => ['General', 'Request'],
    'allowed_offer_status' => ['Pending', 'Negotiate', 'Canceled', 'Accepted'],

    'allowed_booking_status' => ['Pending', 'Done', 'Timeout','Approved'],

    'allowed_booking_offer_status' => ['Pending', 'Accepted'],
];