<?php

return [
    'seat-identity-provider' => [
        'name'          => 'Identity Provider',
        'icon'          => 'fas fa-id-card',
        'route_segment' => 'seat-identityprovider',
        'entries'       => [
            [
                'name'       => 'Applications',
                'label'      => 'seat-identity-provider::seat.applications',
                'plural'     => true,
                'icon'       => 'fas fa-browser',
                'route'      => 'seat-identity-provider.saml.applications.index',
                'permission' => 'seat-identity-provider.view',
            ],
            // [
            //     'name'       => 'Logs',
            //     'label'      => 'web::seat.log',
            //     'plural'     => true,
            //     'icon'       => 'fas fa-list',
            //     'route'      => 'seat-identityprovider.logs',
            //     'permission' => 'seat-identityprovider.logs_review',
            // ],
            // [
            //     'name'       => 'Settings',
            //     'label'      => 'seat-identityprovider::seat.settings',
            //     'icon'       => 'fas fa-cogs',
            //     'route'      => 'seat-identityprovider.settings',
            //     'permission' => 'global.superuser',
            // ],
        ],
        'permission'    => 'seat-identity-provider.view',
    ],
];
