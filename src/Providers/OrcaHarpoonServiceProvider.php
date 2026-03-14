<?php

namespace MakeDev\OrcaHarpoon\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use MakeDev\OrcaHarpoon\Http\Middleware\InjectOrcaHarpoon;
use MakeDev\OrcaHarpoon\Livewire\Harpoon;

class OrcaHarpoonServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerLivewireComponents();
        $this->registerMiddleware();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        //
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            $this->basePath('config/orcaharpoon.php') => config_path('orcaharpoon.php'),
        ], 'orcaharpoon-config');

        $this->mergeConfigFrom($this->basePath('config/orcaharpoon.php'), 'orcaharpoon');
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $this->publishes([
            $this->basePath('resources/views') => resource_path('views/vendor/orcaharpoon'),
        ], 'orcaharpoon-views');

        $this->loadViewsFrom($this->basePath('resources/views'), 'orcaharpoon');
    }

    /**
     * Register Livewire components for this package.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component('orcaharpoon-harpoon', Harpoon::class);
    }

    /**
     * Push the InjectOrcaHarpoon middleware to the web group.
     */
    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', InjectOrcaHarpoon::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function basePath(string $path = ''): string
    {
        return dirname(__DIR__, 2).($path ? '/'.$path : '');
    }
}
