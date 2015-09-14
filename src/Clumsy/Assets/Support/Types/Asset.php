<?php
namespace Clumsy\Assets\Support\Types;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\HTML;

class Asset
{
    protected $replace_embedded_assets;

    protected $path;

    protected $v = null;

    public function __construct($attributes)
    {
        $replace = Config::get('clumsy/assets::config.replace-embedded-assets');
        $this->setReplaceEmbeddedAssets($replace);

        if (!isset($attributes['inline'])) {
            $inline = Config::get('clumsy/assets::config.inline');
            $this->inline = $inline;
        }

        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getPath()
    {
        $replace = array(
            '{{environment}}' => App::environment(),
            '{{locale}}'      => App::getLocale(),
        );

        return str_replace(array_keys($replace), array_values($replace), $this->path);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getReplaceEmbeddedAssets()
    {
        return $this->replace_embedded_assets;
    }

    public function setReplaceEmbeddedAssets($replace)
    {
        $this->replace_embedded_assets = $replace;
    }

    protected function embeddedAssetReplace()
    {
        if ($this->replace_embedded_assets instanceof Closure) {
            return $this->replace_embedded_assets($this);
        }

        if (!$this->replace_embedded_assets) {
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

    public function __toString()
    {
        $method = $this->method;
        return $this->inline ? $this->inline() : HTML::$method($this->pathWithVersion());
    }
}
