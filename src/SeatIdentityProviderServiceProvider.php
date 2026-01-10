<?php


namespace Vita\Seat\IdentityProvider;

use Illuminate\Routing\Router;
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
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        #$this->registerDatabaseSeeders(ScheduleSeeder::class);

        $this->mergeConfigFrom(__DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
        #$this->mergeConfigFrom(__DIR__ . '/Config/seat-connector.config.php', 'seat-connector.config');

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
        return 'https://github.com/vita/seat-identity-provider';
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
        return 'vita';
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
        if (! $this->app->routesAreCached()) {
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
}
