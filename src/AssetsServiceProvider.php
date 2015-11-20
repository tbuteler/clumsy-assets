<?php

namespace Clumsy\Assets;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class AssetsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/assets.php', 'clumsy.assets.app');
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'clumsy.asset-loader');

        $this->app['clumsy.assets'] = $this->app->make('Clumsy\Assets\Asset');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Kernel $kernel)
    {
        if (!$this->app->runningInConsole()) {
            $kernel->pushMiddleware('Clumsy\Assets\Http\Middleware\PrintAssets');
        }

        $this->publishes([
            __DIR__.'/config/assets.php' => config_path('clumsy/assets/app.php'),
            __DIR__.'/config/config.php' => config_path('clumsy/asset-loader.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'clumsy.assets',
        ];
    }
}
