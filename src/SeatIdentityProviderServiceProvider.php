<?php

namespace Mrmaniak\Seat\IdentityProvider;

use DateInterval;
use Illuminate\Routing\Router;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Mrmaniak\Seat\IdentityProvider\Models\OidcKeypair;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\AccessTokenRepository;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\AuthCodeRepository;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\ClientRepository;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\RefreshTokenRepository;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Claim;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Scope;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\ScopeRepository;
use Mrmaniak\Seat\IdentityProvider\Repositories\IdentitySeatRepository;
use Nyholm\Psr7\Response;
use OpenIDConnect\ClaimExtractor;
use OpenIDConnect\Claims\ClaimSet;
use OpenIDConnect\Grant\AuthCodeGrant as OidcAuthCodeGrant;
use OpenIDConnect\IdTokenResponse;
use OpenIDConnect\Repositories\IdentityRepository;
use OpenIDConnect\Laravel\LaravelCurrentRequestService;
use Seat\Services\AbstractSeatPlugin;

/**
 * Class SeatIdPServiceProvider.
 */
class SeatIdentityProviderServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->addCommands();
        $this->addMigrations();
        $this->addRoutes();
        $this->addViews();
        $this->addTranslations();
        $this->addApiEndpoints();
        $this->addEvents();
        $this->configureOAuth2Server();
        $this->configureOpenIdConnect();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
        $this->mergeConfigFrom(__DIR__ . '/Config/oidc.php', 'seat-identity-provider.oidc');

        $this->registerPermissions(__DIR__ . '/Config/seat-identity-provider.permissions.php', 'seat-identity-provider');
    }

    /**
     * Return the plugin public name as it should be displayed into settings.
     *
     * @return string
     *
     * @example SeAT Web
     */
    public function getName(): string
    {
        return 'SeAT Identity Provider';
    }

    /**
     * Return the plugin repository address.
     *
     * @example https://github.com/eveseat/web
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/mrmaniak/seat-identity-provider';
    }

    /**
     * Return the plugin technical name as published on package manager.
     *
     * @return string
     *
     * @example web
     */
    public function getPackagistPackageName(): string
    {
        return 'seat-identity-provider';
    }

    /**
     * Return the plugin vendor tag as published on package manager.
     *
     * @return string
     *
     * @example eveseat
     */
    public function getPackagistVendorName(): string
    {
        return 'mrmaniak';
    }

    /**
     * Import migrations.
     */
    private function addMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations/');
    }

    /**
     * Register cli commands.
     */
    private function addCommands(): void
    {

    }

    /**
     * Register views.
     */
    private function addViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-identity-provider');
    }

    /**
     * Import routes.
     */
    private function addRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    /**
     * Import translations.
     */
    private function addTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'seat-identity-provider');
    }

    /**
     * Import API endpoints.
     */
    private function addApiEndpoints(): void
    {

    }

    /**
     * Register events listeners.
     */
    private function addEvents(): void
    {

    }

    /**
     * Configure the League OAuth2 Server with OIDC support.
     */
    private function configureOAuth2Server(): void
    {
        // Bind ClaimExtractor as a singleton with custom claimsets
        $this->app->singleton(ClaimExtractor::class, function ($app) {
            $claimExtractor = new ClaimExtractor();
            $claimExtractor->addClaimSet(new ClaimSet(Scope::User->value, [
                Claim::IsAdmin->value,
            ]));
            $claimExtractor->addClaimSet(new ClaimSet(Scope::Character->value, [
                Claim::CharacterId->value,
                Claim::CharacterName->value,
            ]));
            $claimExtractor->addClaimSet(new ClaimSet(Scope::Corporation->value, [
                Claim::CorporationId->value,
                Claim::AllianceId->value,
            ]));
            $claimExtractor->addClaimSet(new ClaimSet(Scope::Squads->value, [
                Claim::Squads->value,
            ]));

            return $claimExtractor;
        });

        // Bind the AuthorizationServer as a singleton
        $this->app->singleton(AuthorizationServer::class, function ($app) {
            $keypair = OidcKeypair::getActiveKeypair();

            // Create repositories
            $clientRepository = new ClientRepository();
            $scopeRepository = new ScopeRepository();
            $accessTokenRepository = new AccessTokenRepository();
            $authCodeRepository = new AuthCodeRepository();
            $refreshTokenRepository = new RefreshTokenRepository();

            // Create the identity repository for OIDC claims
            $identityRepository = new IdentitySeatRepository();
            $claimExtractor = $app->make(ClaimExtractor::class);

            $currentRequestService = $app->make(LaravelCurrentRequestService::class);

            // Create the response type with OIDC ID token support
            $responseType = new IdTokenResponse(
                $identityRepository,
                $claimExtractor,
                Configuration::forAsymmetricSigner(
                    new Sha256(),
                    InMemory::plainText($keypair->private_key),
                    InMemory::plainText($keypair->public_key)
                ),
                $currentRequestService,
            );

            // Create the server
            $server = new AuthorizationServer(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                $keypair->getPrivateCryptKey(),
                $this->getEncryptionKey(),
                $responseType
            );

            // Get token lifetimes from config
            $accessTokenTtl = new DateInterval(
                'PT' . config('seat-identity-provider.oidc.access_token_lifetime', 60) . 'M'
            );
            $refreshTokenTtl = new DateInterval(
                'PT' . config('seat-identity-provider.oidc.refresh_token_lifetime', 10080) . 'M'
            );
            $authCodeTtl = new DateInterval('PT10M'); // 10 minutes for auth codes

            // Enable the Authorization Code grant with OIDC support
            $authCodeGrant = new OidcAuthCodeGrant(
                $authCodeRepository,
                $refreshTokenRepository,
                $authCodeTtl,
                new Response(),
                $currentRequestService
            );
            $authCodeGrant->setRefreshTokenTTL($refreshTokenTtl);

            $server->enableGrantType($authCodeGrant, $accessTokenTtl);

            // Enable the Refresh Token grant
            $refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepository);
            $refreshTokenGrant->setRefreshTokenTTL($refreshTokenTtl);

            $server->enableGrantType($refreshTokenGrant, $accessTokenTtl);

            return $server;
        });

        // Bind the ResourceServer for validating access tokens
        $this->app->singleton(ResourceServer::class, function ($app) {
            $keypair = OidcKeypair::getActiveKeypair();

            return new ResourceServer(
                new AccessTokenRepository(),
                $keypair->getPublicCryptKey()
            );
        });
    }

    /**
     * Get the encryption key for the OAuth2 server.
     */
    private function getEncryptionKey(): string
    {
        $key = config('app.key');

        // Laravel's APP_KEY is prefixed with 'base64:'
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        // Ensure the key is exactly 32 bytes
        return substr(hash('sha256', $key, true), 0, 32);
    }

    /**
     * Configure OpenID Connect identity repository.
     */
    private function configureOpenIdConnect(): void
    {
        // Bind custom identity repository for OIDC claims
        $this->app->bind(
            IdentityRepository::class,
            IdentitySeatRepository::class
        );
    }
}
