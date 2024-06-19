<?php

return [
    'PERMISSION_LIST' => [

        'CUSTOMER' => [
            [
                'name'    => 'View Customer',
                'slug'    => 'can_view_customer',
                'subject' => 'customer',
            ],
            [
                'name'    => 'Create Customer',
                'slug'    => 'can_create_customer',
                'subject' => 'customer',
            ],
        ],

        'CONTACT' => [
            [
                'name'    => 'View Contact',
                'slug'    => 'can_view_contact',
                'subject' => 'contact',
            ],
        ],

        'CATEGORY' => [
            [
                'name'    => 'View Category',
                'slug'    => 'can_view_category',
                'subject' => 'Menu',
            ],
            [
                'name'    => 'Create Category',
                'slug'    => 'can_create_category',
                'subject' => 'Menu',
            ],
            [
                'name'    => 'Update Category',
                'slug'    => 'can_update_category',
                'subject' => 'Menu',
            ],

            [
                'name'    => 'Delete Category',
                'slug'    => 'can_delete_category',
                'subject' => 'Menu',
            ]
        ],

        'ACL_GROUP' => [
            [
                'name'    => 'View Role',
                'slug'    => 'can_view_acl_role',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create Role',
                'slug'    => 'can_create_acl_role',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Update Role',
                'slug'    => 'can_update_acl_role',
                'subject' => 'ACL',
            ],

            [
                'name'    => 'Delete Role',
                'slug'    => 'can_delete_acl_role',
                'subject' => 'ACL',
            ]
        ],

        'ACL_PERMISSION' => [
            [
                'name'    => 'View Permission',
                'slug'    => 'can_view_acl_permission',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create Permission',
                'slug'    => 'can_create_acl_permission',
                'subject' => 'permission',
            ],
            [
                'name'    => 'Update Permission',
                'slug'    => 'can_update_acl_permission',
                'subject' => 'permission',
            ],

            [
                'name'    => 'Delete Permission',
                'slug'    => 'can_delete_acl_permission',
                'subject' => 'permission',
            ]
        ],

        'ACL_USER' => [
            [
                'name'    => 'View Acl User',
                'slug'    => 'can_view_acl_user',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create Acl User',
                'slug'    => 'can_create_acl_user',
                'subject' => 'user',
            ],
            [
                'name'    => 'Update Acl User',
                'slug'    => 'can_update_acl_user',
                'subject' => 'ACL',
            ],

            [
                'name'    => 'Delete Acl User',
                'slug'    => 'can_delete_acl_user',
                'subject' => 'ACL',
            ]
        ],

        'MISC' => [
            [
                'name'    => 'Login To Admin',
                'slug'    => 'can_login_to_admin',
                'subject' => 'misc',
            ],
            [
                'name'    => 'View Dashboard',
                'slug'    => 'can_view_dashboard',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'View ACL',
                'slug'    => 'can_view_acl',
                'subject' => 'misc',
            ],
            [
                'name' => 'View Settings',
                'slug' => 'can_view_settings',
                'subject' => 'misc',
            ]
        ],

    ],

    'PERMISSION_NAMES' => [
        'CAN_VIEW_CUSTOMER'         => 'can_view_customer',
        'CAN_CREATE_CUSTOMER'       => 'can_create_customer',
        'CAN_UPDATE_CUSTOMER'       => 'can_update_customer',
        'CAN_DELETE_CUSTOMER'       => 'can_delete_customer',
        'CAN_VIEW_CATEGORY'         => 'can_view_category',
        'CAN_CREATE_CATEGORY'       => 'can_create_category',
        'CAN_UPDATE_CATEGORY'       => 'can_update_category',
        'CAN_DELETE_CATEGORY'       => 'can_delete_category',
        'CAN_VIEW_ACL_ROLE'         => 'can_view_acl_role',
        'CAN_CREATE_ACL_ROLE'       => 'can_create_acl_role',
        'CAN_UPDATE_ACL_ROLE'       => 'can_update_acl_role',
        'CAN_DELETE_ACL_ROLE'       => 'can_delete_acl_role',
        'CAN_VIEW_ACL_PERMISSION'   => 'can_view_acl_permission',
        'CAN_CREATE_ACL_PERMISSION' => 'can_create_acl_permission',
        'CAN_UPDATE_ACL_PERMISSION' => 'can_update_acl_permission',
        'CAN_DELETE_ACL_PERMISSION' => 'can_delete_acl_permission',
        'CAN_VIEW_ACL_USER'         => 'can_view_acl_user',
        'CAN_CREATE_ACL_USER'       => 'can_create_acl_user',
        'CAN_UPDATE_USER_GROUP'     => 'can_update_user_group',
        'CAN_UPDATE_ACL_USER'       => 'can_update_acl_user',
        'CAN_DELETE_ACL_USER'       => 'can_delete_acl_user',
        'CAN_LOGIN_TO_ADMIN'        => 'can_login_to_admin',
        'CAN_VIEW_DASHBOARD'        => 'can_view_dashboard',
        'CAN_VIEW_ACL'              => 'can_view_acl',
        'CAN_VIEW_CONTACT'          => 'can_view_contact',
    ]
];
