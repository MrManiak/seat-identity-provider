<?php

use Mrmaniak\Seat\IdentityProvider\Http\Middleware\ValidateOAuth2Token;
use OpenIDConnect\Laravel\JwksController;
use OpenIDConnect\Laravel\DiscoveryController;
use Seat\Web\Http\Controllers\Auth\LoginController;

Route::group([
    'namespace' => 'Mrmaniak\Seat\IdentityProvider\Http\Controllers',
], function (): void {
    Route::group([
        'prefix' => 'seat-identity-provider',
        'middleware' => ['web', 'auth', 'locale'],
    ], function (): void {
        // SAML Application Management - Admin routes (security permission) - MUST BE FIRST
        Route::group([
            'middleware' => 'can:seat-identity-provider.security',
        ], function (): void {
            Route::prefix('saml/applications')->name('seat-identity-provider.saml.applications.')->group(function (): void {
                Route::get('/create', 'SamlApplicationController@create')->name('create');
                Route::post('/fetch-metadata', 'SamlApplicationController@fetchMetadata')->name('fetch-metadata');
                Route::post('/', 'SamlApplicationController@store')->name('store');
                Route::get('/{application}/edit', 'SamlApplicationController@edit')->name('edit');
                Route::put('/{application}', 'SamlApplicationController@update')->name('update');
                Route::delete('/{application}', 'SamlApplicationController@destroy')->name('destroy');
            });
        });

        // SAML Application Management - View routes (view permission)
        Route::group([
            'middleware' => 'can:seat-identity-provider.view',
        ], routes: function (): void {
            Route::prefix('saml/applications')->name('seat-identity-provider.saml.applications.')->group(function (): void {
                Route::get('/', 'SamlApplicationController@index')->name('index');
                Route::get('/{application}/metadata', 'SamlApplicationController@metadata')->name('metadata');
            });
        });

        // OIDC Application Management - Admin routes (security permission)
        Route::group([
            'middleware' => 'can:seat-identity-provider.security',
        ], function (): void {
            Route::prefix('oidc/applications')->name('seat-identity-provider.oidc.applications.')->group(function (): void {
                Route::get('/create', 'OidcApplicationController@create')->name('create');
                Route::post('/', 'OidcApplicationController@store')->name('store');
                Route::get('/{application}/edit', 'OidcApplicationController@edit')->name('edit');
                Route::put('/{application}', 'OidcApplicationController@update')->name('update');
                Route::delete('/{application}', 'OidcApplicationController@destroy')->name('destroy');
                Route::post('/{application}/regenerate-secret', 'OidcApplicationController@regenerateSecret')->name('regenerate-secret');
            });
        });

        // OIDC Application Management - View routes (view permission)
        Route::group([
            'middleware' => 'can:seat-identity-provider.view',
        ], function (): void {
            Route::prefix('oidc/applications')->name('seat-identity-provider.oidc.applications.')->group(function (): void {
                Route::get('/', 'OidcApplicationController@index')->name('index');
            });
        });
    });

    // SAML SSO endpoints
    Route::group([
        'prefix' => 'saml',
        'middleware' => ['web', 'auth'],
    ], function (): void {
        Route::match(['get', 'post'], '/{application}/sso', 'SamlController@sso')->name('seat-identity-provider.saml.sso');
        Route::match(['get', 'post'], '/{application}/slo', 'SamlController@slo')->name('seat-identity-provider.saml.slo');
    });

    // OAuth2/OIDC Discovery and JWKS (public endpoints - no authentication required)
    Route::group([
        'middleware' => ['web'],
    ], function (): void {
        Route::get('/.well-known/openid-configuration', 'OidcController@discovery')
            ->name('seat-identity-provider.oidc.discovery');
        Route::get('/oidc/jwks', 'OidcController@jwks')
            ->name('seat-identity-provider.oidc.jwks');
    });

    // OAuth2 Authorization endpoint (requires web auth - user must be logged in)
    Route::group([
        'middleware' => ['web', 'auth'],
    ], function (): void {
        Route::get('/oauth2/authorize', 'OAuth2Controller@authorize')
            ->name('seat-identity-provider.oauth2.authorize');
        Route::post('/oauth2/authorize', 'OAuth2Controller@approveAuthorization')
            ->name('seat-identity-provider.oauth2.approve');
    });

    // OAuth2 Token endpoint (public - client authenticates via credentials)
    Route::group([
        'middleware' => ['api'],
    ], function (): void {
        Route::post('/oauth2/token', 'OAuth2Controller@token')
            ->name('seat-identity-provider.oauth2.token');
    });

    // OIDC UserInfo endpoint (requires OAuth2 token authentication)
    Route::group([
        'middleware' => [ValidateOAuth2Token::class],
    ], function (): void {
        Route::get('/oidc/userinfo', 'OidcController@userinfo')
            ->name('seat-identity-provider.oidc.userinfo');
    });

    // Hack to get standard AuthenticationException to route to SeAT login page
    Route::get('login', [LoginController::class,'login'])
        ->name('login');
});
