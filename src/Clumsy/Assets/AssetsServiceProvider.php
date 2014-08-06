<?php namespace Clumsy\Assets;

use Illuminate\Support\ServiceProvider;

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
        $app = $this->app;

        $this->package('clumsy/assets');

        $app['config']->package('clumsy/assets', $this->guessPackagePath() . '/config');

        $app['asset'] = new \Clumsy\Assets\Asset;
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        if(!$app->runningInConsole())
        {
            $app->after(function($request, $response) use($app)
            {
                $this->triggerEvents($response);
            });
        }
    }

    public function triggerEvents($response)
    {
        $content = $response->getContent();

        $header_content = array_flatten(array(
            \Event::fire('Print styles'),
            \Event::fire('Print scripts'),
        ));
        $header_content = implode(PHP_EOL, array_filter($header_content));

        $pos = strripos($content, '</head>');

        if ($pos !== false)
        {
            $content = substr($content, 0, $pos) . $header_content . substr($content, $pos);
        }

        $footer_content = \Event::fire('Print footer scripts');
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
