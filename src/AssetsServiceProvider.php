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
        $this->mergeConfigFrom(__DIR__.'/config/assets.php', 'clumsy/assets');
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'clumsy/assets/config');

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
            __DIR__.'/config/assets.php' => config_path('assets.php'),
            __DIR__.'/config/config.php' => config_path('vendor/clumsy/assets/config.php'),
        ], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'clumsy.assets',
        );
    }
}
