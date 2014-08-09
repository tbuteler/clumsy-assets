<?php namespace Clumsy\Assets;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Clumsy\Assets\Asset;

class AssetsServiceProvider extends ServiceProvider {

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
        $this->package('clumsy/assets');

        $this->app['config']->package('clumsy/assets', $this->guessPackagePath() . '/config');

        $this->app['asset'] = new Asset;
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if(!$this->app->runningInConsole())
        {
            $this->app->after(array($this, 'triggerEvents'));
        }
    }

    public function triggerEvents($request, $response)
    {
        $content = $response->getContent();

        $header_content = array_flatten(array(
            Event::fire('Print styles'),
            Event::fire('Print scripts'),
        ));
        $header_content = implode(PHP_EOL, array_filter($header_content));

        $pos = strripos($content, '</head>');

        if ($pos !== false)
        {
            $content = substr($content, 0, $pos) . $header_content . substr($content, $pos);
        }

        $footer_content = Event::fire('Print footer scripts');
        $footer_content = implode(PHP_EOL, array_filter($footer_content));

        $pos = strripos($content, '</body>');

        if ($pos !== false)
        {
            $content = substr($content, 0, $pos) . $footer_content . substr($content, $pos);
        }

        $response->setContent($content);
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
