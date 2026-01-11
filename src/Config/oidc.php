<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes
    |--------------------------------------------------------------------------
    |
    | Configure the expiration times for various token types.
    | Values are in minutes.
    |
    */
    'access_token_lifetime' => env('SEAT_IDP_OIDC_ACCESS_TOKEN_LIFETIME', 60),
    'refresh_token_lifetime' => env('SEAT_IDP_OIDC_REFRESH_TOKEN_LIFETIME', 10080), // 7 days
    'id_token_lifetime' => env('SEAT_IDP_OIDC_ID_TOKEN_LIFETIME', 60)
];
