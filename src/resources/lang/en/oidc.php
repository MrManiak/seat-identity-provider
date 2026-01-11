<?php

return [
    'application'        => 'application',
    'applications'       => 'OIDC Applications',
    'create_application' => 'Create OIDC Application',
    'edit_application'   => 'Edit OIDC Application',

    'client_id'     => 'Client ID',
    'client_secret' => 'Client Secret',
    'redirect_uris' => 'Redirect URIs',
    'allowed_scopes' => 'Allowed Scopes',

    // Authorization page
    'authorize_application' => 'Authorize Application',
    'authorization_request' => 'Authorization Request',
    'authorization_prompt'  => 'This application is requesting access to your account.',
    'requested_scopes'      => 'Requested Permissions',
    'authorize'             => 'Authorize',
    'deny'                  => 'Deny',
    'logged_in_as'          => 'Logged in as',

    'scopes' => [
        'openid'           => 'OpenID Connect base scope',
        'profile'          => 'User profile (name, username)',
        'email'            => 'User email address',
        'seat:user'   => 'SeAT admin status',
        'seat:character'   => 'EVE character information',
        'seat:corporation' => 'EVE corporation information',
        'seat:squads'      => 'SeAT squad memberships',
    ],

    // Key management
    'keys'                 => 'OIDC Keys',
    'key_id'               => 'Key ID',
    'algorithm'            => 'Algorithm',
    'expires_at'           => 'Expires At',
    'generate_key'         => 'Generate New Key',
    'activate_key'         => 'Activate',
    'delete_key'           => 'Delete',
    'key_generated'        => 'New keypair generated successfully.',
    'key_activated'        => 'Keypair activated successfully.',
    'key_deleted'          => 'Keypair deleted successfully.',
    'cannot_delete_active' => 'Cannot delete the active keypair.',
    'key_rotation_warning' => 'Rotating keys will invalidate existing tokens signed with the old key.',
];
