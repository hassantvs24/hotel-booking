<?php

return [
    'site_info' => [
        'name'    => env('APP_NAME', 'Laravel'),
        'address' => '123 Main St',
        'city'    => 'Springfield',
        'state'   => 'IL',
        'zip'     => '62701',
        'phone'   => '217-555-5555',
        'email'   => 'site@gmail.com'
    ],

    'developed_by' => [
        'company'         => 'Infinity Flame Soft',
        'company_website' => 'https://infinityflamesoft.com/'
    ],

    'portal_filter_not_exists' => ['/'],

    'assets' => [
        'admin'  => [
            'css' => [
                [
                    'src'  => 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700',
                    'type' => 'cdn',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'nucleo-icons.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'nucleo-svg.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'nucleo-svg.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'argon-dashboard.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                    'id'   => 'pagestyle'
                ],
                [
                    'src'  => 'select2.min.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'toastr.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src'  => 'custom.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ]
            ],

            'js' => [
                [
                    'src'  => 'plugins/jquery-3.7.1.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'core/popper.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'core/bootstrap.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'https://kit.fontawesome.com/42d5adcbca.js',
                    'type' => 'cdn',
                ],
                [
                    'src'  => 'plugins/perfect-scrollbar.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/smooth-scrollbar.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/dragula/dragula.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/jkanban/jkanban.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/chartjs.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'customs/chart.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'customs/sidebar.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/sweetalert.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'https://buttons.github.io/buttons.js',
                    'type' => 'cdn',
                ],
                [
                    'src'  => 'argon-dashboard.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/select2.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'plugins/toastr.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'customs/custom.js',
                    'type' => 'local'
                ]
            ]
        ],
        'portal' => [
            'css' => [
                [
                    'src'  => 'bootstrap.min.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet'
                ],
                [
                    'src'  => 'jquery-ui.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet'
                ],
                [
                    'src'  => 'style.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet'
                ],
                [
                    'src'  => 'responsive.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet'
                ],
                [
                    'src'  => 'custom.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet'
                ]
            ],
            'js'  => [
                [
                    'src'  => 'jquery.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'bootstrap.bundle.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'jquery.easing.min.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'jquery-ui.js',
                    'type' => 'local',
                ],
                [
                    'src'  => 'main.js',
                    'type' => 'local',
                ]
            ]
        ]
    ],

    'sidebar' => [
        'items' => [
            [
                'name'       => 'Dashboard',
                'route'      => 'admin.dashboard',
                'icon'       => 'ni ni-shop text-primary',
                'key'        => 'admin/dashboard*',
                'permission' => 'can_view_dashboard',
                'children'   => []
            ],
            [
                'name'       => 'Rooms',
                'route'      => null,
                'icon'       => 'fa fa-bed text-primary',
                'key'        => 'admin/rooms*',
                'permission' => 'can_view_rooms',
                'children'   => [
                    [
                        'name'       => 'Rooms',
                        'route'      => 'admin.rooms.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/rooms',
                        'permission' => 'can_view_room',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Room Types',
                        'route'      => 'admin.rooms.room-types.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/rooms/room-types',
                        'permission' => 'can_view_room_type',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Bed Types',
                        'route'      => 'admin.rooms.bed-types.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/rooms/bed-types',
                        'permission' => 'can_view_bed_type',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Price Types',
                        'route'      => 'admin.rooms.price-types.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/rooms/price-types',
                        'permission' => 'can_view_price_type',
                        'children'   => []
                    ]
                ]
            ],
            [
                'name'       => 'Properties',
                'route'      => null,
                'icon'       => 'ni ni-building text-primary',
                'key'        => 'admin/properties*',
                'permission' => 'can_view_properties',
                'children'   => [
                    [
                        'name'       => 'Property Rules',
                        'route'      => 'admin.properties.rules.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/properties/rules',
                        'permission' => 'can_view_property_rule',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Properties',
                        'route'      => 'admin.properties.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/properties',
                        'permission' => 'can_view_property',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Property Categories',
                        'route'      => 'admin.properties.categories.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/properties/property-categories',
                        'permission' => 'can_view_property_category',
                        'children'   => []
                    ],
                ]
            ],
            [
                'name'       => 'Reviews',
                'route'      => null,
                'icon'       => 'fa fa-star text-primary',
                'key'        => 'admin/reviews*',
                'permission' => 'can_view_review',
                'children'   => [
                    [
                        'name'       => 'Reviews',
                        'route'      => 'admin.reviews.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/reviews',
                        'permission' => 'can_view_review',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Categories',
                        'route'      => 'admin.reviews.categories.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/reviews/categories',
                        'permission' => 'can_view_review_category',
                        'children'   => []
                    ]
                ]

            ],
            [
                'name'       => 'Facilities',
                'route'      => null,
                'icon'       => 'ni ni-support-16 text-primary',
                'key'        => 'admin/facilities*',
                'permission' => 'can_view_facilities',
                'children'   => [
                    [
                        'name'       => 'Sub Facilities',
                        'route'      => 'admin.facilities.sub-facilities.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/facilities/sub-facilities',
                        'permission' => 'can_view_sub_facility',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Facilities',
                        'route'      => 'admin.facilities.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/facilities',
                        'permission' => 'can_view_facility',
                        'children'   => []
                    ]
                ]
            ],
            [
                'name'       => 'Surroundings',
                'route'      => null,
                'icon'       => 'ni ni-map-big text-primary',
                'key'        => 'admin/surroundings*',
                'permission' => 'can_view_surroundings',
                'children'   => [
                    [
                        'name'       => 'Surrounding Places',
                        'route'      => 'admin.surroundings.surrounding-places.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/surroundings/surrounding-places',
                        'permission' => 'can_view_surrounding_place',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Surroundings',
                        'route'      => 'admin.surroundings.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/surroundings',
                        'permission' => 'can_view_surrounding',
                        'children'   => []
                    ]
                ]
            ],
            [
                'name'       => 'Locations',
                'route'      => null,
                'icon'       => 'ni ni-world text-primary',
                'key'        => 'admin/places*',
                'permission' => 'can_view_locations',
                'children'   => [
                    [
                        'name'       => 'Places',
                        'route'      => 'admin.places.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/places',
                        'permission' => 'can_view_place',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Cities',
                        'route'      => 'admin.places.cities.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/places/cities',
                        'permission' => 'can_view_city',
                        'children'   => []
                    ],
                    [
                        'name'       => 'States',
                        'route'      => 'admin.places.states.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/places/states',
                        'permission' => 'can_view_state',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Countries',
                        'route'      => 'admin.places.countries.index',
                        'icon'       => 'ni ni-world text-default',
                        'key'        => 'admin/places/countries',
                        'permission' => 'can_view_country',
                        'children'   => []
                    ],
                ]
            ],
            [
                'name'       => 'Access Control',
                'route'      => null,
                'icon'       => 'ni ni-key-25 text-info',
                'key'        => 'admin/acl*',
                'permission' => 'can_view_acl',
                'children'   => [
                    [
                        'name'       => 'Roles',
                        'route'      => 'admin.acl.roles.index',
                        'icon'       => 'ni ni-circle-08 text-default',
                        'key'        => 'admin/acl/roles',
                        'permission' => 'can_view_acl_role',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Permissions',
                        'route'      => 'admin.acl.permissions.index',
                        'icon'       => 'ni ni-lock-circle-open text-default',
                        'permission' => 'can_view_acl_permission',
                        'key'        => 'admin/acl/permissions',
                    ],
                    [
                        'name'       => 'Users',
                        'route'      => 'admin.acl.users.index',
                        'icon'       => 'ni ni-single-02 text-default',
                        'key'        => 'admin/acl/users',
                        'permission' => 'can_view_acl_user',
                        'children'   => []
                    ]
                ]
            ],
            [
                'name'       => 'Settings',
                'route'      => 'admin.settings.index',
                'icon'       => 'ni ni-settings text-primary',
                'key'        => 'admin/settings',
                'permission' => 'can_view_settings',
                'children'   => []
            ]
        ]
    ],

    'portal_navbar' => [
        'items' => [
            [
                'name'     => 'Home',
                'route'    => 'portal.home',
                'key'      => 'portal/home',
                'children' => [],
            ],
            [
                'name'     => 'Deals',
                'route'    => null,
                'key'      => 'portal/deals',
                'children' => [],
            ],
            [
                'name'     => 'Properties',
                'route'    => 'portal.properties.index',
                'key'      => 'portal/properties',
                'children' => [],
            ],
            [
                'name'     => 'Hotels',
                'route'    => 'portal.hotels.index',
                'key'      => 'portal/hotels',
                'children' => [],
            ],
            [
                'name'     => 'Support',
                'route'    => null,
                'key'      => 'portal/support',
                'children' => [],
            ]
        ]
    ],

    'allowedMediaType' => ['image', 'video', 'pdf'],

    'allowedFileExtensionToUpload' => [
        'image' => ['jpeg', 'jpg', 'png', 'svg'],
        'video' => ['mp4'],
        'pdf'   => ['pdf'],
    ],

    'allowedMediaRole' => [
        'other',
        'profile_image',
        'city_image',
        'place_image',
        'place_icon',
        'facility_icon',
        'surrounding_icon',
        'property_icon',
        'property_image',
        'room_icon',
        'room_image',
        'bed_icon',
        'bed_image',
        'gallery_image',
        'primary_image',
        'review_icon'
    ],

    'settings' => [
        'valueType' => ['text', 'image', 'video', 'bool'],

        'entries' => [
            [
                'key'          => 'app_name',
                'value'        => 'Rate Locker',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => true,
                'is_deletable' => false,
            ],
            [
                'key'          => 'app_version',
                'value'        => '1',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => true,
                'is_deletable' => false,
            ],
            [
                'key'          => 'app_debug',
                'value'        => '1',
                'value_type'   => 'bool',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'locale',
                'value'        => 'en-GB',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'time_zone',
                'value'        => 'Asia/Dhaka',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            // Frontend Currency Symbol Depended on this key
            [
                'key'          => 'currency',
                'value'        => 'GBP',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'currency_symbol',
                'value'        => '£',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'date_format',
                'value'        => 'DD-MMM-YYYY',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'country_code',
                'value'        => '+88',
                'value_type'   => 'text',
                'group'        => 'app',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            // address
            [
                'key'          => 'address',
                'value'        => '96-98 The Broadway, Sunderland SR4 8NX',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'city',
                'value'        => 'Sylhet',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'state',
                'value'        => 'Sylhet',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'postcode',
                'value'        => 'Sylhet-3100',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'latitude',
                'value'        => '54.894032',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'longitude',
                'value'        => '-1.433804',
                'value_type'   => 'text',
                'group'        => 'address',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            // Social Settings
            [
                'key'          => 'ios_app_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'android_app_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'twitter_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'tumblr_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'linkedin_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'instagram_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'pinterest_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'facebook_url',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'trip_advisor',
                'value'        => 'https://ekta-kichu.com',
                'value_type'   => 'text',
                'group'        => 'social',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            // Contact
            [
                'key'          => 'email',
                'value'        => 'admin@company.com',
                'value_type'   => 'text',
                'group'        => 'contact',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'phone',
                'value'        => '01719894414',
                'value_type'   => 'text',
                'group'        => 'contact',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'fax',
                'value'        => '01712345678',
                'value_type'   => 'text',
                'group'        => 'contact',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'landline',
                'value'        => '01712345678',
                'value_type'   => 'text',
                'group'        => 'contact',
                'admin_only'   => false,
                'is_deletable' => false,
            ],

            // maintenance Settings
            [
                'key'          => 'maintenance_mode',
                'value'        => 0,
                'value_type'   => 'bool',
                'group'        => 'maintenance',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'maintenance_text',
                'value'        => 'We are currently under maintenance. Please check back later.',
                'value_type'   => 'text',
                'group'        => 'maintenance',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            // Site Specific Settings
            [
                'key'          => 'site_title',
                'value'        => 'Rate Locker',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_language',
                'value'        => 'en-US',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'home_page_title',
                'value'        => 'Plan Your Dream Trip In <span>Budget</span>',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'home_page_subtitle',
                'value'        => 'We are here to help you to find the best place to stay in your budget',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'home_page_number_of_slider',
                'value'        => 3,
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_tip_title',
                'value'        => 'Hotel Booking Within Your Budget Is Never <span>Easier</span>',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_tip_description',
                'value'        => 'Pickup your phone today, and install our mobile app. After that, just one click away to find your dream hotel to your destination within your budget',
                'value_type'   => 'text',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_tip_image',
                'value'        => 'sample/tip.jpg',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'mobile_app_qr_code',
                'value'        => 'sample/qr-code.png',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_logo',
                'value'        => 'sample/logo-big.jpg',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_logo_small',
                'value'        => 'sample/logo-big.jpg',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'site_logo_large',
                'value'        => 'sample/logo-big.jpg',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'favicon',
                'value'        => '',
                'value_type'   => 'image',
                'group'        => 'site',
                'admin_only'   => false,
                'is_deletable' => false,
            ],

            // Copyright and Credit Section
            [
                'key'          => 'designed_by',
                'value'        => 'Infinity Flame Soft',
                'value_type'   => 'text',
                'group'        => 'footer',
                'admin_only'   => false,
                'is_deletable' => false,
            ],
            [
                'key'          => 'copyright_text',
                'value'        => 'copyright &copy; 2021 || All right reserved by ',
                'value_type'   => 'text',
                'group'        => 'footer',
                'admin_only'   => false,
                'is_deletable' => false,
            ]
        ],
    ],
];
