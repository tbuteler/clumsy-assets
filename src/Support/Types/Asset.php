<?php

namespace Clumsy\Assets\Support\Types;

use Closure;
use Exception;
use Illuminate\Support\Facades\App;

abstract class Asset
{
    protected $replaceEmbeddedAssets;

    protected $path;

    protected $version = null;

    public function __construct($attributes)
    {
        $replace = config('clumsy.asset-loader.replace-embedded-assets');
        $this->setReplaceEmbeddedAssets($replace);

        if (!isset($attributes['inline'])) {
            $inline = config('clumsy.asset-loader.inline');
            $this->inline = $inline;
        }

        if (!isset($attributes['hash'])) {
            $hash = config('clumsy.asset-loader.hash');
            $this->hash = $hash;
        }

        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }

        if (is_array($this->path)) {
            // If no 'default' key is set, use first path as default
            $default = array_get($this->path, 'default', head($this->path));
            // Fetch path according to current environment, with default as fallback
            $this->path = array_get($this->path, app()->environment(), $default);
        }
    }

    public function getRawPath()
    {
        return $this->path;
    }

    public function getPath()
    {
        return app('clumsy.assets')->processReplacements($this);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function shouldReplace($placeholder)
    {
        return str_contains($this->path, app('clumsy.assets')->wrapReplacer($placeholder));
    }

    public function getReplaceEmbeddedAssets()
    {
        return $this->replaceEmbeddedAssets;
    }

    public function setReplaceEmbeddedAssets($replace)
    {
        $this->replaceEmbeddedAssets = $replace;
    }

    protected function embeddedAssetReplace()
    {
        if ($this->replaceEmbeddedAssets instanceof Closure) {
            return $this->replaceEmbeddedAssets($this);
        }

        if (!$this->replaceEmbeddedAssets) {
            return false;
        }

        return '$0'.url($this->getFolderPath()).'/';
    }

    protected function getFolderPath()
    {
        $folder = $this->getPath();
        if ($this->isLocal()) {
            $folder = explode('/', $folder);
            if (count($folder) > 1) {
                array_pop($folder);
                $folder = implode('/', $folder);
            }
        }

        return $folder;
    }

    protected function pathWithVersion()
    {
        $path = $this->getPath();
        if ($this->isLocal() && $this->hash) {
            try {
                // Check Laravel's version and use Elixir or Mix accordingly
                list($major, $minor) = array_map('intval', explode('.', app()->version()));
                if ($major < 5) {
                    throw new Exception('Unsupported Laravel version.');
                }
                $path = $minor < 4 ? elixir($path) : mix($path);
            } catch (Exception $e) {
                $path = $this->getPath();
            }
        }

        $suffix = null;
        if (!$this->inline && $this->version && !$this->shouldReplace('version')) {
            $suffix = '?v='.(string)$this->version;
        }

        return "{$path}{$suffix}";
    }

    protected function content()
    {
        if ($this->isLocal() && $this->exists()) {
            $content = app('files')->get(public_path($this->getPath()));

            $contentToReplace = $this->embeddedAssetReplace();
            if ($contentToReplace) {
                $content = preg_replace('/url\(\'?"?(?!(|\'|")http)(?!(|\'|")data)/', $contentToReplace, $content);
            }

            return $content;
        }

        return null;
    }

    protected function exists()
    {
        return file_exists(public_path($this->getPath()));
    }

    public function isExternal()
    {
        return preg_match('/^(\/\/|https?:\/\/)/', $this->getPath());
    }

    public function isLocal()
    {
        return !$this->isExternal();
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param  array  $attributes
     * @return string
     */
    public function attributes($attributes)
    {
        $html = [];

        foreach ((array)$attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);
            if (!is_null($element)) {
                $html[] = $element;
            }
        }

        return count($html) > 0 ? ' '.implode(' ', $html) : '';
    }

    /**
     * Build a single attribute element.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        if (is_numeric($key)) {
            $key = $value;
        }

        if (!is_null($value)) {
            return $key.'="'.e($value).'"';
        }
    }

    public function printStyle($url)
    {
        $attributes = [
            'media' => 'all',
            'type'  => 'text/css',
            'rel'   => 'stylesheet',
            'href'  => url($url),
        ];

        return '<link'.$this->attributes($attributes).'>'.PHP_EOL;
    }

    public function printScript($url)
    {
        $attributes['src'] = url($url);

        return '<script'.$this->attributes($attributes).'></script>'.PHP_EOL;
    }

    public function __toString()
    {
        $method = 'print'.studly_case($this->method);
        return $this->inline ? $this->inline() : $this->$method($this->pathWithVersion());
    }
}
