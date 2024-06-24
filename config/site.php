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
                'name'       => 'Properties',
                'route'      => null,
                'icon'       => 'ni ni-building text-primary',
                'key'        => 'admin/properties*',
                'permission' => 'can_view_properties',
                'children'   => [
                    [
                        'name'       => 'Property Categories',
                        'route'      => 'admin.properties.categories.index',
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/properties/property-categories',
                        'permission' => 'can_view_property_category',
                        'children'   => []
                    ],
                    [
                        'name'       => 'Properties',
                        'route'      => null,
                        'icon'       => 'ni ni-building text-default',
                        'key'        => 'admin/properties',
                        'permission' => 'can_view_property',
                        'children'   => []
                    ]
                ]
            ],
            [
                'name'       => 'Facilities',
                'route'      => null,
                'icon'       => 'ni ni-building text-primary',
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
                'route'      => null,
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
    ]
];
