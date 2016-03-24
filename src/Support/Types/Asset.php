<?php

namespace Clumsy\Assets\Support\Types;

use Closure;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class Asset
{
    protected $replaceEmbeddedAssets;

    protected $path;

    protected $v = null;

    public function __construct($attributes)
    {
        $replace = config('clumsy.asset-loader.replace-embedded-assets');
        $this->setReplaceEmbeddedAssets($replace);

        if (!isset($attributes['inline'])) {
            $inline = config('clumsy.asset-loader.inline');
            $this->inline = $inline;
        }

        if (!isset($attributes['elixir'])) {
            $elixir = config('clumsy.asset-loader.elixir');
            $this->elixir = $elixir;
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

    public function getPath()
    {
        return app('clumsy.assets')->processReplacements($this->path);
    }

    public function setPath($path)
    {
        $this->path = $path;
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
        if ($this->isLocal() && $this->elixir) {
            try {
                $path = elixir($path);
            } catch (Exception $e) {
                $path = $this->getPath();
            }
        }

        $suffix = !$this->inline && $this->v ? '?v=' . (string)$this->v : null;
        return "{$path}{$suffix}";
    }

    protected function content()
    {
        if ($this->isLocal() && $this->exists()) {

            $content = File::get(public_path($this->getPath()));

            $content_to_replace = $this->embeddedAssetReplace();
            if ($content_to_replace) {
                $content = preg_replace('/url\(\'?"?(?!(|\'|")http)(?!(|\'|")data)/', $content_to_replace, $content);
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
