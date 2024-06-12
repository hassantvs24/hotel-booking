<?php

return [
    'PERMISSION_LIST' => [

        'CUSTOMER' => [
            [
                'name'    => 'View customer',
                'slug'    => 'can_view_customer',
                'subject' => 'customer',
            ],
            [
                'name'    => 'Create customer',
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
                'name'    => 'View category',
                'slug'    => 'can_view_category',
                'subject' => 'Menu',
            ],
            [
                'name'    => 'Create category',
                'slug'    => 'can_create_category',
                'subject' => 'Menu',
            ],
            [
                'name'    => 'Update category',
                'slug'    => 'can_update_category',
                'subject' => 'Menu',
            ],

            [
                'name'    => 'Delete category',
                'slug'    => 'can_delete_category',
                'subject' => 'Menu',
            ]
        ],

        'ACL_GROUP' => [
            [
                'name'    => 'View group',
                'slug'    => 'can_view_acl_group',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create group',
                'slug'    => 'can_create_acl_group',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Update group',
                'slug'    => 'can_update_acl_group',
                'subject' => 'ACL',
            ],

            [
                'name'    => 'Delete group',
                'slug'    => 'can_delete_acl_group',
                'subject' => 'ACL',
            ]
        ],

        'ACL_PERMISSION' => [
            [
                'name'    => 'View permission',
                'slug'    => 'can_view_acl_permission',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create permission',
                'slug'    => 'can_create_acl_permission',
                'subject' => 'permission',
            ],
            [
                'name'    => 'Update permission',
                'slug'    => 'can_update_acl_permission',
                'subject' => 'permission',
            ],

            [
                'name'    => 'Delete permission',
                'slug'    => 'can_delete_acl_permission',
                'subject' => 'permission',
            ]
        ],

        'ACL_USER' => [
            [
                'name'    => 'View acl user',
                'slug'    => 'can_view_acl_user',
                'subject' => 'ACL',
            ],
            [
                'name'    => 'Create acl user',
                'slug'    => 'can_create_acl_user',
                'subject' => 'user',
            ],
            [
                'name'    => 'Update acl user',
                'slug'    => 'can_update_acl_user',
                'subject' => 'ACL',
            ],

            [
                'name'    => 'Delete acl user',
                'slug'    => 'can_delete_acl_user',
                'subject' => 'ACL',
            ]
        ],

        'MISC' => [
            [
                'name'    => 'Login to admin',
                'slug'    => 'can_login_to_admin',
                'subject' => 'misc',
            ],
            [
                'name'    => 'View dashboard',
                'slug'    => 'can_view_dashboard',
                'subject' => 'misc',
            ],
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
        'CAN_VIEW_ACL_GROUP'        => 'can_view_acl_group',
        'CAN_CREATE_ACL_GROUP'      => 'can_create_acl_group',
        'CAN_UPDATE_ACL_GROUP'      => 'can_update_acl_group',
        'CAN_DELETE_ACL_GROUP'      => 'can_delete_acl_group',
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
        'CAN_VIEW_CONTACT'          => 'can_view_contact',
    ]
];
