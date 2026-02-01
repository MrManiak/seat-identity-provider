<?php

return [
    'seat-identity-provider' => [
        'name'          => 'Identity Provider',
        'icon'          => 'fas fa-id-card',
        'route_segment' => 'seat-identityprovider',
        'entries'       => [
            [
                'name'       => 'SAML Applications',
                'label'      => 'seat-identity-provider::seat.saml_applications',
                'plural'     => true,
                'icon'       => 'fas fa-key',
                'route'      => 'seat-identity-provider.saml.applications.index',
                'permission' => 'seat-identity-provider.view',
            ],
            [
                'name'       => 'OIDC Applications',
                'label'      => 'seat-identity-provider::seat.oidc_applications',
                'plural'     => true,
                'icon'       => 'fas fa-lock',
                'route'      => 'seat-identity-provider.oidc.applications.index',
                'permission' => 'seat-identity-provider.view',
            ],
            [
                'name'       => 'OIDC Keys',
                'label'      => 'seat-identity-provider::seat.oidc_keys',
                'plural'     => true,
                'icon'       => 'fas fa-certificate',
                'route'      => 'seat-identity-provider.oidc.keys.index',
                'permission' => 'seat-identity-provider.view',
            ],
        ],
        'permission'    => 'seat-identity-provider.view',
    ],
];
