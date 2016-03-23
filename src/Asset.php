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

    protected function unknownAsset($assetName)
    {
        if ($this->app['config']->get('clumsy.asset-loader.silent')) {
            // Fail silently, unless debug is on
            return null;
        }

        throw new UnknownAssetException("Unknown asset \"$assetName\"");
    }

    public function sets()
    {
        return $this->container->getSets();
    }

    public function get($asset, $override = [])
    {
        $attributes = array_get($this->container->getAssets(), $asset);

        if (is_null($attributes)) {
            return $this->unknownAsset($asset);
        }

        return $this->container->assetAsObject(array_merge($attributes, (array)$override));
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

    public function load($assets, $priority = 25)
    {
        $registered = $this->all();

        foreach ((array)$assets as $asset) {

            if (!isset($registered[$asset])) {
                return $this->unknownAsset($asset);
            }

            if (isset($registered[$asset]['req'])) {
                foreach ((array)$registered[$asset]['req'] as $requirement) {
                    // If a 'header' asset has requirements, make sure they are loaded
                    // in the header as well, regardless of original set
                    if ($registered[$asset]['set'] === 'header') {
                        $this->move($requirement, $registered[$asset]['set']);
                    }
                    $this->load($requirement, $priority);
                }
            }

            $this->on($registered[$asset]['set'], array_merge(['key' => $asset], $registered[$asset]), $priority);
        }
    }

    public function loadNew($set, $key, array $attributes = [], $priority = 25)
    {
        if ($this->register($set, $key, $attributes)) {
            $this->load($key, $priority);
        }

        return false;
    }

    public function fonts($fonts, $options = '')
    {
        $provider = $this->app['config']->get('clumsy.asset-loader.font-provider');
        $method = "{$provider}Fonts";
        if (method_exists($this, $method)) {
            return $this->$method($fonts, $options);
        }

        return false;
    }

    public function googleFonts($fonts, $options = '')
    {
        $this->loadNew('styles', sha1(print_r($fonts, true)), [
            'type'     => 'google-font',
            'fonts'    => $fonts,
            'options'  => $options,
        ], 50);
    }

    public function typekit($kitId)
    {
        $this->loadNew('styles', "typekit-{$kitId}", [
            'type'   => 'typekit',
            'kit_id' => $kitId,
        ], 50);
    }

    public function unique($id, Closure $closure)
    {
        if ($this->container->isUnique($id)) {
            $this->container->addUnique($id);
            call_user_func($closure);

            return true;
        }

        return false;
    }

    public function json($id, $value, $replace = false)
    {
        $this->updateArray($this->app['config']->get('clumsy.asset-loader.json-variable'), $id, $value, $replace);
    }

    protected function updateArray($namespace, $id, $value, $replace = false)
    {
        if ($replace || !is_array($value) || empty($value)) {
            return $this->container->setArray("{$namespace}.{$id}", $value);
        }

        return $this->container->addArray("{$namespace}.{$id}", $value);
    }

    public function replacer($key, $function)
    {
        $this->replacers[$key] = $function;
    }

    public function setReplacerDelimiters($left, $right)
    {
        $this->replacerDelimiterLeft = $left;
        $this->replacerDelimiterRight = $right;
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

    public function localePathReplacer()
    {
        return $this->app->getLocale();
    }

    public function environmentPathReplacer()
    {
        return $this->app->environment();
    }

    /*
    |--------------------------------------------------------------------------
    | Deprecated aliases
    |--------------------------------------------------------------------------
    |
    | To be removed on 1.0
    |
    */

    public function enqueue($assets, $priority = 25)
    {
        return $this->load($assets, $priority);
    }

    public function enqueueNew($set, $key, array $attributes = [], $priority = 25)
    {
        return $this->loadNew($set, $key, $attributes, $priority);
    }

    public function once($id, Closure $closure)
    {
        return $this->unique($id, $closure);
    }

    public function font($fonts, $options = '')
    {
        return $this->fonts($fonts, $options);
    }
}
