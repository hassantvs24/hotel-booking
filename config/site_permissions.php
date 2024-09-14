<?php

return [
    'PERMISSION_LIST' => [

        'CUSTOMER' => [
            [
                'name' => 'View Customer',
                'slug' => 'can_view_customer',
                'subject' => 'customer',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Customer',
                'slug' => 'can_create_customer',
                'subject' => 'customer',
                'permission_type' => 'global',
            ],
        ],

        'CONTACT' => [
            [
                'name' => 'View Contact',
                'slug' => 'can_view_contact',
                'subject' => 'contact',
                'permission_type' => 'global',
            ],
        ],

        'BED_TYPE' => [
            [
                'name' => 'View Property Bed Type',
                'slug' => 'can_view_property_bed_type',
                'subject' => 'bed_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Bed Type',
                'slug' => 'can_create_property_bed_type',
                'subject' => 'bed_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Bed Type',
                'slug' => 'can_update_property_bed_type',
                'subject' => 'bed_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Delete Property Bed Type',
                'slug' => 'can_delete_property_bed_type',
                'subject' => 'bed_type',
                'permission_type' => 'property',
            ]
        ],

        'BOOKING' => [
            [
                'name' => 'View Booking',
                'slug' => 'can_view_booking',
                'subject' => 'booking',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Booking',
                'slug' => 'can_create_booking',
                'subject' => 'booking',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Booking',
                'slug' => 'can_update_booking',
                'subject' => 'booking',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Delete Booking',
                'slug' => 'can_delete_booking',
                'subject' => 'booking',
                'permission_type' => 'property',
            ]
        ],

        'BOOKING_REQUEST' => [
            [
                'name' => 'View Booking Request',
                'slug' => 'can_view_booking_request',
                'subject' => 'booking_request',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Booking Request',
                'slug' => 'can_create_booking_request',
                'subject' => 'booking_request',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Booking Request',
                'slug' => 'can_update_booking_request',
                'subject' => 'booking_request',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Booking Request',
                'slug' => 'can_delete_booking_request',
                'subject' => 'booking_request',
                'permission_type' => 'property',
            ]
        ],
        'ROOM' => [
            [
                'name' => 'View Room',
                'slug' => 'can_view_room',
                'subject' => 'room',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Room',
                'slug' => 'can_create_room',
                'subject' => 'room',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Room',
                'slug' => 'can_update_room',
                'subject' => 'room',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Room',
                'slug' => 'can_delete_room',
                'subject' => 'room',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property Room',
                'slug' => 'can_view_property_room',
                'subject' => 'room',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Room',
                'slug' => 'can_create_property_room',
                'subject' => 'room',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Room',
                'slug' => 'can_update_property_room',
                'subject' => 'room',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Room',
                'slug' => 'can_delete_property_room',
                'subject' => 'room',
                'permission_type' => 'property',
            ],
        ],

        'ROOM_TYPE' => [
            [
                'name' => 'View Property Room Type',
                'slug' => 'can_view_property_room_type',
                'subject' => 'room_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Room Type',
                'slug' => 'can_create_property_room_type',
                'subject' => 'room_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Room Type',
                'slug' => 'can_update_property_room_type',
                'subject' => 'room_type',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Room Type',
                'slug' => 'can_delete_property_room_type',
                'subject' => 'room_type',
                'permission_type' => 'property',
            ]
        ],

        'PRICE_TYPE' => [
            [
                'name' => 'View Property Price Type',
                'slug' => 'can_view_property_price_type',
                'subject' => 'price_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Price Type',
                'slug' => 'can_create_property_price_type',
                'subject' => 'price_type',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Price Type',
                'slug' => 'can_update_property_price_type',
                'subject' => 'price_type',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Price Type',
                'slug' => 'can_delete_property_price_type',
                'subject' => 'price_type',
                'permission_type' => 'property',
            ]
        ],

        'OFFER' => [
            [
                'name' => 'View Property Offer',
                'slug' => 'can_view_property_offer',
                'subject' => 'offer',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Offer',
                'slug' => 'can_create_property_offer',
                'subject' => 'offer',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Offer',
                'slug' => 'can_update_property_offer',
                'subject' => 'offer',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Offer',
                'slug' => 'can_delete_property_offer',
                'subject' => 'offer',
                'permission_type' => 'property',
            ]
        ],

        'REVIEW_CATEGORY' => [
            [
                'name' => 'View Property Review Category',
                'slug' => 'can_view_property_review_category',
                'subject' => 'review_category',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Review Category',
                'slug' => 'can_create_property_review_category',
                'subject' => 'review_category',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Review Category',
                'slug' => 'can_update_property_review_category',
                'subject' => 'review_category',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Review Category',
                'slug' => 'can_delete_property_review_category',
                'subject' => 'review_category',
                'permission_type' => 'property',
            ]
        ],

        'REVIEW' => [
            [
                'name' => 'View Property Review',
                'slug' => 'can_view_property_review',
                'subject' => 'review',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Review',
                'slug' => 'can_create_property_review',
                'subject' => 'review',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Review',
                'slug' => 'can_update_property_review',
                'subject' => 'review',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Review',
                'slug' => 'can_delete_property_review',
                'subject' => 'review',
                'permission_type' => 'property',
            ]
        ],

        'PROPERTY_CATEGORY' => [
            [
                'name' => 'View Property Category',
                'slug' => 'can_view_property_category',
                'subject' => 'property_category',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Property Category',
                'slug' => 'can_create_property_category',
                'subject' => 'property_category',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Property Category',
                'slug' => 'can_update_property_category',
                'subject' => 'property_category',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Property Category',
                'slug' => 'can_delete_property_category',
                'subject' => 'property_category',
                'permission_type' => 'global',
            ]
        ],

        'PROPERTY' => [
            [
                'name' => 'View Property',
                'slug' => 'can_view_property',
                'subject' => 'property',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Property',
                'slug' => 'can_create_property',
                'subject' => 'property',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Property',
                'slug' => 'can_update_property',
                'subject' => 'property',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Property',
                'slug' => 'can_delete_property',
                'subject' => 'property',
                'permission_type' => 'global',
            ]
        ],

        'PROPERTY_RULE' => [
            [
                'name' => 'View Property Rule',
                'slug' => 'can_view_property_rule',
                'subject' => 'property_rule',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Rule',
                'slug' => 'can_create_property_rule',
                'subject' => 'property_rule',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Rule',
                'slug' => 'can_update_property_rule',
                'subject' => 'property_rule',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Rule',
                'slug' => 'can_delete_property_rule',
                'subject' => 'property_rule',
                'permission_type' => 'property',
            ]
        ],

        'SURROUNDING' => [
            [
                'name' => 'View Surrounding',
                'slug' => 'can_view_surrounding',
                'subject' => 'surrounding',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Surrounding',
                'slug' => 'can_create_surrounding',
                'subject' => 'surrounding',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Surrounding',
                'slug' => 'can_update_surrounding',
                'subject' => 'surrounding',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Surrounding',
                'slug' => 'can_delete_surrounding',
                'subject' => 'surrounding',
                'permission_type' => 'global',
            ]
        ],

        'SURROUNDING_PLACE' => [
            [
                'name' => 'View Surrounding Place',
                'slug' => 'can_view_surrounding_place',
                'subject' => 'surrounding_place',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Surrounding Place',
                'slug' => 'can_create_surrounding_place',
                'subject' => 'surrounding_place',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Surrounding Place',
                'slug' => 'can_update_surrounding_place',
                'subject' => 'surrounding_place',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Surrounding Place',
                'slug' => 'can_delete_surrounding_place',
                'subject' => 'surrounding_place',
                'permission_type' => 'global',
            ]
        ],

        'FACILITY' => [
            [
                'name' => 'View Property Facility',
                'slug' => 'can_view_property_facility',
                'subject' => 'facility',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Facility',
                'slug' => 'can_create_property_facility',
                'subject' => 'facility',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property Facility',
                'slug' => 'can_update_property_facility',
                'subject' => 'facility',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Facility',
                'slug' => 'can_delete_facility',
                'subject' => 'facility',
                'permission_type' => 'property',
            ]
        ],

        'SUB_FACILITY' => [
            [
                'name' => 'View Property Sub Facility',
                'slug' => 'can_view_property_sub_facility',
                'subject' => 'sub_facility',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property Sub Facility',
                'slug' => 'can_create_property_sub_facility',
                'subject' => 'sub_facility',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Sub Facility',
                'slug' => 'can_update_sub_facility',
                'subject' => 'sub_facility',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property Sub Facility',
                'slug' => 'can_delete_property_sub_facility',
                'subject' => 'sub_facility',
                'permission_type' => 'property',
            ]
        ],

        'COUNTRY' => [
            [
                'name' => 'View Country',
                'slug' => 'can_view_country',
                'subject' => 'country',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Country',
                'slug' => 'can_create_country',
                'subject' => 'country',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Country',
                'slug' => 'can_update_country',
                'subject' => 'country',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Country',
                'slug' => 'can_delete_country',
                'subject' => 'country',
                'permission_type' => 'global',
            ]
        ],

        'STATE' => [
            [
                'name' => 'View State',
                'slug' => 'can_view_state',
                'subject' => 'state',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create State',
                'slug' => 'can_create_state',
                'subject' => 'state',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update State',
                'slug' => 'can_update_state',
                'subject' => 'state',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete State',
                'slug' => 'can_delete_state',
                'subject' => 'state',
                'permission_type' => 'global',
            ]
        ],

        'CITY' => [
            [
                'name' => 'View City',
                'slug' => 'can_view_city',
                'subject' => 'city',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create City',
                'slug' => 'can_create_city',
                'subject' => 'city',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update City',
                'slug' => 'can_update_city',
                'subject' => 'city',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete City',
                'slug' => 'can_delete_city',
                'subject' => 'city',
                'permission_type' => 'global',
            ]
        ],

        'PLACE' => [
            [
                'name' => 'View Place',
                'slug' => 'can_view_place',
                'subject' => 'place',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Place',
                'slug' => 'can_create_place',
                'subject' => 'place',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Place',
                'slug' => 'can_update_place',
                'subject' => 'place',
                'permission_type' => 'global',
            ],

            [
                'name' => 'Delete Place',
                'slug' => 'can_delete_place',
                'subject' => 'place',
                'permission_type' => 'global',
            ]
        ],

        'ACL_GROUP' => [
            [
                'name' => 'View Role',
                'slug' => 'can_view_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Role',
                'slug' => 'can_create_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Role',
                'slug' => 'can_update_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Delete Role',
                'slug' => 'can_delete_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property ACL Role',
                'slug' => 'can_view_property_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property ACL Role',
                'slug' => 'can_create_property_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property ACL Role',
                'slug' => 'can_update_property_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Delete Property ACL Role',
                'slug' => 'can_delete_property_acl_role',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ]
        ],

        'ACL_PERMISSION' => [
            [
                'name' => 'View Permission',
                'slug' => 'can_view_acl_permission',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create Permission',
                'slug' => 'can_create_acl_permission',
                'subject' => 'permission',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update Permission',
                'slug' => 'can_update_acl_permission',
                'subject' => 'permission',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Delete Permission',
                'slug' => 'can_delete_acl_permission',
                'subject' => 'permission',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property ACL Permission',
                'slug' => 'can_view_property_acl_permission',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property ACL Permission',
                'slug' => 'can_create_property_acl_permission',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property ACL Permission',
                'slug' => 'can_update_property_acl_permission',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Delete Property ACL Permission',
                'slug' => 'can_delete_property_acl_permission',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ]
        ],

        'ACL_USER' => [
            [
                'name' => 'View Acl User',
                'slug' => 'can_view_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Create ACL User',
                'slug' => 'can_create_acl_user',
                'subject' => 'user',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Update ACL User',
                'slug' => 'can_update_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'Delete ACL User',
                'slug' => 'can_delete_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property ACL User',
                'slug' => 'can_view_property_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Create Property ACL User',
                'slug' => 'can_create_property_acl_user',
                'subject' => 'user',
                'permission_type' => 'property',
            ],
            [
                'name' => 'Update Property ACL User',
                'slug' => 'can_update_property_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],

            [
                'name' => 'Delete Property ACL User',
                'slug' => 'can_delete_property_acl_user',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ]
        ],

        'MISC' => [
            [
                'name' => 'Login To Admin',
                'slug' => 'can_login_to_admin',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Dashboard',
                'slug' => 'can_view_dashboard',
                'subject' => 'ACL',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property Dashboard',
                'slug' => 'can_view_property_dashboard',
                'subject' => 'ACL',
                'permission_type' => 'property',
            ],
            [
                'name' => 'View ACL',
                'slug' => 'can_view_acl',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property Rooms',
                'slug' => 'can_view_property_rooms',
                'subject' => 'misc',
                'permission_type' => 'property',
            ],
            [
                'name' => 'View Properties',
                'slug' => 'can_view_properties',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Locations',
                'slug' => 'can_view_locations',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property Facilities',
                'slug' => 'can_view_property_facilities',
                'subject' => 'misc',
                'permission_type' => 'property',
            ],
            [
                'name' => 'View Surroundings',
                'slug' => 'can_view_surroundings',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Settings',
                'slug' => 'can_view_settings',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Property Settings',
                'slug' => 'can_view_property_settings',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Reviews',
                'slug' => 'can_view_reviews',
                'subject' => 'misc',
                'permission_type' => 'global',
            ],
            [
                'name' => 'View Offers',
                'slug' => 'can_view_offers',
                'subject' => 'misc',
                'permission_type' => 'property',
            ]
        ],

    ],

    'PERMISSION_NAMES' => [
        'CAN_VIEW_COUNTRY' => 'can_view_country',

        'CAN_VIEW_CUSTOMER' => 'can_view_customer',
        'CAN_CREATE_CUSTOMER' => 'can_create_customer',
        'CAN_UPDATE_CUSTOMER' => 'can_update_customer',
        'CAN_DELETE_CUSTOMER' => 'can_delete_customer',

        'CAN_VIEW_CATEGORY' => 'can_view_category',
        'CAN_CREATE_CATEGORY' => 'can_create_category',
        'CAN_UPDATE_CATEGORY' => 'can_update_category',
        'CAN_DELETE_CATEGORY' => 'can_delete_category',

        'CAN_VIEW_ACL_ROLE' => 'can_view_acl_role',
        'CAN_CREATE_ACL_ROLE' => 'can_create_acl_role',
        'CAN_UPDATE_ACL_ROLE' => 'can_update_acl_role',
        'CAN_DELETE_ACL_ROLE' => 'can_delete_acl_role',

        'CAN_VIEW_ACL_PERMISSION' => 'can_view_acl_permission',
        'CAN_CREATE_ACL_PERMISSION' => 'can_create_acl_permission',
        'CAN_UPDATE_ACL_PERMISSION' => 'can_update_acl_permission',
        'CAN_DELETE_ACL_PERMISSION' => 'can_delete_acl_permission',

        'CAN_UPDATE_USER_GROUP' => 'can_update_user_group',

        'CAN_VIEW_ACL_USER' => 'can_view_acl_user',
        'CAN_CREATE_ACL_USER' => 'can_create_acl_user',
        'CAN_UPDATE_ACL_USER' => 'can_update_acl_user',
        'CAN_DELETE_ACL_USER' => 'can_delete_acl_user',

        'CAN_VIEW_SETTINGS' => 'can_view_settings',
        'CAN_VIEW_DASHBOARD' => 'can_view_dashboard',

        'CAN_VIEW_ACL' => 'can_view_acl',
        'CAN_VIEW_LOCATIONS' => 'can_view_locations',
        'CAN_VIEW_FACILITIES' => 'can_view_facilities',

        'CAN_VIEW_CONTACT' => 'can_view_contact',
        'CAN_LOGIN_TO_ADMIN' => 'can_login_to_admin',
    ],
];
