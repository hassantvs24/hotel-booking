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
        'company' => 'Infinity Flame Soft',
        'company_website' => 'https://infinityflamesoft.com/'
    ],

    'assets' => [
        'admin' => [
            'css' => [
                [
                    'src' => 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700',
                    'type' => 'cdn',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src' => 'nucleo-icons.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src' => 'nucleo-svg.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src' => 'nucleo-svg.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src' => 'argon-dashboard.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                    'id' => 'pagestyle'
                ],
                [
                    'src' => 'select2.min.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ],
                [
                    'src' => 'custom.css',
                    'type' => 'local',
                    'rel'  => 'stylesheet',
                ]
            ],

            'js' => [
                [
                    'src' => 'plugins/jquery-3.7.1.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'core/popper.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'core/bootstrap.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'https://kit.fontawesome.com/42d5adcbca.js',
                    'type' => 'cdn',
                ],
                [
                    'src' => 'plugins/perfect-scrollbar.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/smooth-scrollbar.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/dragula/dragula.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/jkanban/jkanban.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/chartjs.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'customs/chart.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'customs/sidebar.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/sweetalert.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'https://buttons.github.io/buttons.js',
                    'type' => 'cdn',
                ],
                [
                    'src' => 'argon-dashboard.min.js',
                    'type' => 'local',
                ],
                [
                    'src' => 'plugins/select2.min.js',
                    'type' => 'local',
                ]
            ]
        ],
                'portal' => [
                    'css' => [
                        [
                            'src'=>'bootstrap.min.css',
                            'type'=>'local',
                            'rel'=>'stylesheet'
                        ],
                        [
                            'src'=>'jquery-ui.css',
                            'type'=>'local',
                            'rel'=>'stylesheet'
                        ],
                        [
                            'src'=>'style.css',
                            'type'=>'local',
                            'rel'=>'stylesheet'
                        ],
                        [
                            'src'=>'responsive.css',
                            'type'=>'local',
                            'rel'=>'stylesheet'
                        ]
                    ],
                    'js' => [
                        [
                            'src' => 'jquery.min.js',
                            'type' => 'local',
                        ],
                        [
                            'src' => 'bootstrap.bundle.min.js',
                            'type' => 'local',
                        ],
                        [
                            'src' => 'jquery.easing.min.js',
                            'type' => 'local',
                        ],
                        [
                            'src' => 'jquery-ui.js',
                            'type' => 'local',
                        ],
                        [
                            'src' => 'main.js',
                            'type' => 'local',
                        ]
                    ]
                ]
    ],

    'sidebar' => [
        'items' => [
            [
                'name' => 'Dashboard',
                'route' => 'admin.dashboard',
                'icon' => 'ni ni-shop text-primary',
                'key' => 'admin/dashboard*',
                'children' => []
            ],
            [
                'name' => 'Access Control',
                'route' => null,
                'icon' => 'ni ni-key-25 text-info',
                'key' => 'admin/acl*',
                'children' => [
                    [
                        'name' => 'Roles',
                        'route' => 'admin.acl.roles.index',
                        'icon' => 'ni ni-circle-08 text-default',
                        'key' => 'admin/acl/roles',
                        'children' => []
                    ],
                    [
                        'name' => 'Permissions',
                        'route' => 'admin.acl.permissions.index',
                        'icon' => 'ni ni-lock-circle-open text-default',
                        'key' => 'admin/acl/permissions',
                    ],
                    [
                        'name' => 'Users',
                        'route' => 'admin.acl.users.index',
                        'icon' => 'ni ni-single-02 text-default',
                        'key' => 'admin/acl/users',
                        'children' => []
                    ]
                ]
            ],
            [
                'name' => 'Settings',
                'route' => null,
                'icon' => 'ni ni-settings text-primary',
                'key' => 'admin/settings',
                'children' => []
            ]
        ]
    ],
];
