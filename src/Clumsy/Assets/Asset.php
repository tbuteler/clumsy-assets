<?php
namespace Clumsy\Assets;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\HTML;
use Clumsy\Assets\Support\Container;
use Clumsy\Assets\Support\Exceptions\UnknownAssetException;

class Asset
{
    protected $container;

    public function __construct(Application $app, Container $container)
    {
        $this->app = $app;
        $this->container = $container;
    }

    public function sets()
    {
        return $this->container->getSets();
    }

    public function all()
    {
        return $this->container->getAssets();
    }

    public function dump($set)
    {
        return $this->container->dump($set);
    }

    protected function on($set, $asset, $priority)
    {
        $this->container->add($set, $asset, $priority);
    }

    public function register($set, $key, array $attributes = array())
    {
        return $this->container->register($set, $key, $attributes);
    }

    public function batchRegister($assets)
    {
        $default = array(
            'set'  => false,
            'path' => false,
        );

        foreach ($assets as $key => $asset) {
            $asset = array_merge($default, (array)$asset);
            extract($asset);

            if (!$set || !$key || !$path) {
                continue;
            }

            $this->register($set, $key, $asset);
        }
    }

    public function enqueueNew($set, $key, array $attributes = array(), $priority = 25)
    {
        if ($this->register($set, $key, $attributes)) {
            $this->enqueue($key, $priority);
        }

        return false;
    }

    public function enqueue($asset, $priority = 25)
    {
        $assets = $this->all();

        if (!isset($assets[$asset])) {
            if ($this->app['config']->get('clumsy/assets::config.silent')) {
                // Fail silently, unless debug is on
                return false;
            }

            throw new UnknownAssetException();
        }

        if (isset($assets[$asset]['req'])) {
            foreach ((array)$assets[$asset]['req'] as $requirement) {
                $this->enqueue($requirement, $priority);
            }
        }

        $this->on($assets[$asset]['set'], array_merge(array('key' => $asset), $assets[$asset]), $priority);
    }

    public function json($id, $array, $replace = false)
    {
        $this->updateArray('handover', $id, $array, $replace);
    }

    public function updateArray($namespace, $id, $array, $replace = false)
    {
        if ($replace) {
            $this->container->setArray($namespace, array($id => $array));
        } else {
            $this->container->addArray($namespace, array($id => $array));
        }
    }

    public function unique($id, Closure $closure)
    {
        $container = $this->container;

        if (!in_array($id, $container->unique)) {
            $this->container->unique[] = $id;

            call_user_func($closure);

            return true;
        }

        return false;
    }

    public function font($fonts, $options = '')
    {
        $provider = $this->app['config']->get('clumsy/assets::config.font-provider');

        $this->enqueueNew('styles', sha1(print_r($fonts, true)), array(
            'priority' => 50,
            'type'     => "{$provider}-font",
            'fonts'    => $fonts,
            'options'  => $options,
        ));
    }

    public function once($id, Closure $closure)
    {
        return $this->unique($id, $closure);
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this, 'updateArray'), array_flatten(func_get_args()));
    }
}
