<?php
namespace Clumsy\Assets;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        $path = __DIR__.'/../..';
        $this->package('clumsy/assets', 'clumsy/assets', $path);

        $this->app['clumsy.assets'] = $this->app->make('Clumsy\Assets\Asset');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->runningInConsole()) {
            $this->app->after(array($this, 'triggerEvents'));
        }
    }

    public function triggerEvents($request, $response)
    {
        $content = $response->getContent();

        if (!$content) {
            return false;
        }

        foreach (array_keys($this->app['clumsy.assets']->sets()) as $set) {
            Event::listen($this->getEvent($set), function () use ($set) {
                return $this->app['clumsy.assets']->dump($set);
            }, 25);
        }

        $header_content = array_flatten(array(
            Event::fire('Print styles'),
            Event::fire('Print scripts'),
        ));
        $header_content = implode(PHP_EOL, array_filter($header_content));

        $pos = strripos($content, '</head>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $header_content . substr($content, $pos);
        }

        $footer_content = Event::fire('Print footer scripts');
        $footer_content = implode(PHP_EOL, array_filter($footer_content));

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos) . $footer_content . substr($content, $pos);
        }

        $response->setContent($content);
    }

    protected function getEvent($set)
    {
        switch ($set) {
            case 'styles':
                return 'Print styles';

            case 'header':
                return 'Print scripts';

            default:
                return 'Print footer scripts';
        }
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
