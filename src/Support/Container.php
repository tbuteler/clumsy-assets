<?php

namespace Clumsy\Assets\Support;

use Event;
use Illuminate\Foundation\Application;
use Clumsy\Assets\Support\Types\Json;
use Clumsy\Assets\Support\Exceptions\UnknownAssetTypeException;

class Container
{
    protected $unique = [];

    protected $object_sets = [
        'styles',
        'header',
        'footer',
    ];

    protected $sets = [];

    protected $assets = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->assets = $this->app['config']->get('clumsy.assets.app');
    }

    public function getSets()
    {
        return $this->sets;
    }

    public function getSet($key)
    {
        return isset($this->sets[$key]) ? $this->sets[$key] : false;
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function isUnique($id)
    {
        return !in_array($id, $this->unique);
    }

    public function addUnique($id)
    {
        $this->unique[] = $id;
    }

    public function register($set, $key, array $attributes = [])
    {
        if (!isset($this->assets[$key])) {
            $this->assets[$key] = array_merge(array('set' => $set), $attributes);
            return true;
        }

        return false;
    }

    public function add($set, $asset, $priority = 25)
    {
        $this->primeSet($set);

        $asset['type'] = isset($asset['type']) ? $asset['type'] : $this->getDefaultAssetType($set);
        $key = $asset['key'];

        foreach (array_keys($this->sets[$set]) as $i => $p) {
            if (array_key_exists($key, $this->sets[$set][$p])) {
                // If asset is already in queue, either upgrade priority or ignore enqueue
                if ($priority > $p) {
                    unset($this->sets[$set][$p][$key]);
                } else {
                    return;
                }
            }
        }

        if (!isset($this->sets[$set][$priority])) {
            $this->sets[$set][$priority] = [];
        }

        $this->sets[$set][$priority] = array_merge($this->sets[$set][$priority], array($key => $asset));

        krsort($this->sets[$set]);
    }

    public function move($asset, $set, $clear = true)
    {
        if (isset($this->assets[$asset])) {

            $current = $this->assets[$asset]['set'];

            // Styles cannot be moved
            if ($current === 'styles') {
                return false;
            }

            if ($clear && $set !== $current && $this->getSet($current)) {
                foreach (array_keys($this->sets[$current]) as $i => $p) {
                    if (array_key_exists($asset, $this->sets[$current][$p])) {
                        unset($this->sets[$current][$p][$asset]);
                    }
                }
            }
            $this->assets[$asset]['set'] = $set;
            return true;
        }

        return false;
    }

    public function setArray($set, $array)
    {
        $this->primeSet($set);

        $array = array_dot($array);

        foreach ($array as $key => $value) {
            array_set($this->sets[$set], $key, $array[$key]);
        }
    }

    public function addArray($set, $array)
    {
        $this->primeSet($set);

        $this->sets[$set] = array_merge_recursive($this->sets[$set], $array);
    }

    public function dump($set)
    {
        if ($this->isArrayable($set)) {
            return new Json($set, $this->sets[$set]);
        }

        $content = [];
        foreach ($this->sets[$set] as $assets) {
            foreach ($assets as $asset_attributes) {
                $class = $this->getAssetClassName($asset_attributes['type']);
                if (!class_exists($class)) {
                    throw new UnknownAssetTypeException;
                }

                $content[] = new $class($asset_attributes);
            }
        }

        return implode(PHP_EOL, $content);
    }

    protected function primeSet($key)
    {
        if (!array_key_exists($key, $this->sets)) {
            $this->sets[$key] = [];
        }
    }

    protected function arrayableSets()
    {
        return array_keys(array_except($this->sets, $this->object_sets));
    }

    protected function isArrayable($set)
    {
        return in_array($set, $this->arrayableSets());
    }

    protected function getDefaultAssetType($set)
    {
        switch ($set) {
            case 'styles':
                return 'style';

            default:
                return 'script';
        }
    }

    protected function getAssetClassName($type)
    {
        return '\\Clumsy\\Assets\\Support\\Types\\'.studly_case($type);
    }

    protected function clear()
    {
        foreach (array_keys($this->sets) as $set) {
            $this->sets = [];
        }
    }

    protected function flatten($internal = false)
    {
        $flatten = [];
        foreach ((array)$this->sets as $set => $asset_array) {
            $assets = array_flatten($asset_array);

            $flatten[$set] = !$internal ? $assets : array_filter($assets, function ($asset) {
                return $asset->isLocal();
            });
        }

        return $flatten;
    }
}
