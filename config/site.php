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
                ]
            ],

            'js' => [
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
                    'src' => 'https://buttons.github.io/buttons.js',
                    'type' => 'cdn',
                ],
                [
                    'src' => 'argon-dashboard.min.js',
                    'type' => 'local',
                ]
            ]
        ]
    ]
];
