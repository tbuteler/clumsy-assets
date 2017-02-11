<?php

namespace Clumsy\Assets\Support;

use Illuminate\Foundation\Application;
use Clumsy\Assets\Support\Types\Json;
use Clumsy\Assets\Support\Exceptions\UnknownAssetTypeException;

class Container
{
    protected $objectSets = [
        'styles',
        'header',
        'footer',
    ];

    protected $sets = [];

    protected $assets = [];

    protected $unique = [];

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
        return array_get($this->sets, $key, false);
    }

    public function getAssets()
    {
        return $this->assets;
    }

    public function setAssetKey($asset, $key, $value)
    {
        if (!isset($this->assets[$asset])) {
            return false;
        }

        // Set directly on asset, because $asset could be a string with dots
        // which might get confused with depth
        return array_set($this->assets[$asset], $key, $value);
    }

    public function assetAsObject($attributes)
    {
        $type = isset($attributes['type']) ? $attributes['type'] : $this->getDefaultAssetType($attributes['set']);

        $class = $this->getAssetClassName($type);

        if (!class_exists($class)) {
            throw new UnknownAssetTypeException('Unknown type "'.$type.'" for asset "'.$attributes['key'].'"');
        }

        return new $class($attributes);
    }

    public function register($set, $key, array $attributes = [])
    {
        if (!isset($this->assets[$key])) {
            $this->assets[$key] = array_merge(['set' => $set], $attributes);
            return true;
        }

        return false;
    }

    public function add($set, $asset, $priority = 25)
    {
        $key = $asset['key'];

        foreach (array_keys(array_get($this->sets, $set, [])) as $p) {
            if (array_key_exists($key, array_get($this->sets, "{$set}.{$p}"))) {
                // If asset is already in queue, either upgrade priority
                // or ignore load
                if ($priority <= $p) {
                    return;
                }
                unset($this->sets[$set][$p][$key]);
            }
        }

        array_set(
            $this->sets,
            "{$set}.{$priority}",
            array_merge(array_get($this->sets, "{$set}.{$priority}", []), [$key => $asset])
        );

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
                foreach (array_keys($this->sets[$current]) as $p) {
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

    public function dump($set)
    {
        if ($this->isArrayable($set)) {
            return new Json($set, $this->sets[$set]);
        }

        foreach ($this->sets[$set] as $assets) {
            foreach ($assets as $assetAttributes) {
                $content[] = $this->assetAsObject($assetAttributes);
            }
        }

        return implode(PHP_EOL, $content);
    }

    public function isUnique($id)
    {
        return !in_array($id, $this->unique);
    }

    public function addUnique($id)
    {
        $this->unique[] = $id;
    }

    public function setArray($set, $array)
    {
        array_set($this->sets, $set, $array);
    }

    public function addArray($set, array $array)
    {
        $array = array_dot($array);
        foreach ($array as $key => $value) {
            array_set($this->sets, "{$set}.{$key}", $value);
        }
    }

    protected function arrayableSets()
    {
        return array_keys(array_except($this->sets, $this->objectSets));
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
        $this->sets = [];
    }
}
