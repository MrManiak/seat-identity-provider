<?php

return [
    'seat-identity-provider' => [
        'name'          => 'Identity Provider',
        'icon'          => 'fas fa-id-card',
        'route_segment' => 'seat-identityprovider',
        'entries'       => [
            [
                'name'       => 'SAML Applications',
                'label'      => 'seat-identity-provider::seat.applications',
                'plural'     => true,
                'icon'       => 'fas fa-browser',
                'route'      => 'seat-identity-provider.saml.applications.index',
                'permission' => 'seat-identity-provider.view',
            ],
        ],
        'permission'    => 'seat-identity-provider.view',
    ],
];
