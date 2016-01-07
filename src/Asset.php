<?php

namespace Clumsy\Assets;

use Closure;
use Illuminate\Foundation\Application;
use Clumsy\Assets\Support\Container;
use Clumsy\Assets\Support\Exceptions\UnknownAssetException;

class Asset
{
    protected $container;

    protected $replacerDelimiterLeft = '{{';
    protected $replacerDelimiterRight = '}}';
    protected $replacers = [];

    public function __construct(Application $app, Container $container)
    {
        $this->app = $app;
        $this->container = $container;

        $this->replacer('locale', [$this, 'localePathReplacer']);
        $this->replacer('environment', [$this, 'environmentPathReplacer']);
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

    protected function move($asset, $set)
    {
        $this->container->move($asset, $set);
    }

    public function register($set, $key, array $attributes = [])
    {
        return $this->container->register($set, $key, $attributes);
    }

    public function batchRegister($assets)
    {
        $default = [
            'set'  => false,
            'path' => false,
        ];

        foreach ($assets as $key => $asset) {
            $asset = array_merge($default, (array)$asset);
            extract($asset);

            if (!$set || !$key) {
                continue;
            }

            $this->register($set, $key, $asset);
        }
    }

    public function enqueueNew($set, $key, array $attributes = [], $priority = 25)
    {
        if ($this->register($set, $key, $attributes)) {
            $this->enqueue($key, $priority);
        }

        return false;
    }

    public function enqueue($enqueue, $priority = 25)
    {
        $assets = $this->all();

        foreach ((array)$enqueue as $asset) {

            if (!isset($assets[$asset])) {
                if ($this->app['config']->get('clumsy.asset-loader.silent')) {
                    // Fail silently, unless debug is on
                    return false;
                }

                throw new UnknownAssetException();
            }

            if (isset($assets[$asset]['req'])) {
                foreach ((array)$assets[$asset]['req'] as $requirement) {
                   // If a 'header' asset has requirements, make sure they are enqueued
                    // in the header as well, regardless of original set
                    if ($assets[$asset]['set'] === 'header') {
                        $this->move($requirement, $assets[$asset]['set']);
                    }
                    $this->enqueue($requirement, $priority);
                }
            }

            $this->on($assets[$asset]['set'], array_merge(['key' => $asset], $assets[$asset]), $priority);
        }
    }

    public function json($id, $array, $replace = false)
    {
        $this->updateArray('handover', $id, $array, $replace);
    }

    public function updateArray($namespace, $id, $array, $replace = false)
    {
        if ($replace) {
            $this->container->setArray($namespace, [$id => $array]);
        } else {
            $this->container->addArray($namespace, [$id => $array]);
        }
    }

    public function unique($id, Closure $closure)
    {
        $container = $this->container;

        if ($container->isUnique($id)) {
            $this->container->addUnique($id);
            call_user_func($closure);

            return true;
        }

        return false;
    }

    public function once($id, Closure $closure)
    {
        return $this->unique($id, $closure);
    }

    public function font($fonts, $options = '')
    {
        $provider = $this->app['config']->get('clumsy.asset-loader.font-provider');

        $this->enqueueNew('styles', sha1(print_r($fonts, true)), [
            'type'     => "{$provider}-font",
            'fonts'    => $fonts,
            'options'  => $options,
        ], 50);
    }

    public function typekit($kitId)
    {
        $this->enqueueNew('styles', 'typekit', [
            'type'   => 'typekit',
            'kit_id' => $kitId,
        ], 50);
    }

    public function replacer($key, $function)
    {
        $this->replacers[$key] = $function;
    }

    public function getReplacers()
    {
        return $this->replacers;
    }

    public function processReplacements($string)
    {
        foreach ($this->replacers as $placeholder => $replacement) {
            $placeholder = "{$this->replacerDelimiterLeft}{$placeholder}{$this->replacerDelimiterRight}";
            if (str_contains($string, $placeholder)) {
                $replacement = is_callable($replacement) ? call_user_func($replacement) : $replacement;
                $string = str_replace($placeholder, $replacement, $string);
            }
        }

        return $string;
    }

    public function setReplacerDelimiters($left, $right)
    {
        $this->replacerDelimiterLeft = $left;
        $this->replacerDelimiterRight = $right;
    }

    public function localePathReplacer()
    {
        return $this->app->getLocale();
    }

    public function environmentPathReplacer()
    {
        return $this->app->environment();
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this, 'updateArray'], array_flatten(func_get_args()));
    }
}
