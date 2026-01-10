<?php

Route::group([
    'namespace' => 'Mrmaniak\Seat\IdentityProvider\Http\Controllers',
], function (): void {
    Route::group([
        'prefix'     => 'seat-identity-provider',
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
    });

    // SAML SSO endpoints
    Route::group([
        'prefix'     => 'saml',
        'middleware' => ['web', 'auth'],
    ], function (): void {
        Route::match(['get', 'post'], '/{application}/sso', 'SamlController@sso')->name('seat-identity-provider.saml.sso');
        Route::match(['get', 'post'], '/{application}/slo', 'SamlController@slo')->name('seat-identity-provider.saml.slo');
    });
});
