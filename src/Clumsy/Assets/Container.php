<?php namespace Clumsy\Assets;

use Closure;
use Event;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\HTML;

class Container {

    public $inline;

    public $assets = array();
    
    protected $set = array(

        'styles' => array(),
        'header' => array(),
        'json'   => array(),
        'footer' => array(),
    );

    public $unique = array();
    
    public function __construct()
    {
        $this->inline = Config::get('assets::config.inline');

        $this->assets = Config::get('assets::assets');

        foreach (array_keys($this->set) as $set)
        {
            $container = $this;

            Event::listen($this->event($set), function() use ($container, $set)
            {
                return $container->dump($set);

            }, 25);
        }
    }

    public function register($set, $key, $path, $v = false, $req = false)
    {
        if (!isset($this->assets[$key]))
        {
            $this->assets[$key] = array(
                'set'   => $set,
                'path'  => $path,
            );

            if ($req)
            {
                $this->assets[$key]['req'] = (array)$req;
            }
            
            if ($v)
            {
                $this->assets[$key]['v'] = $v;
            }

            return true;
        }
        
        return false;
    }
    
    public function add($container, $asset, $priority = 25)
    {
        $key = $asset['key'];

        foreach (array_keys($this->set[$container]) as $i => $p)
        {
            if (array_key_exists($key, $this->set[$container][$p]))
            {    
                // If asset is already in queue, either upgrade priority or ignore enqueue
                if ($priority > $p)
                {    
                    unset($this->set[$container][$p][$key]);

                }
                else
                {
                    return;
                }
            }
        }

        if (!isset($this->set[$container][$priority]))
        {
            $this->set[$container][$priority] = array();
        }

        $path = $asset['path'];

        $suffix = !$this->inline && isset($asset['v']) && $asset['v'] != '' ? '?v=' . (string)$asset['v'] : '';

        $this->set[$container][$priority] = array_merge($this->set[$container][$priority], array($key => $path.$suffix));

        krsort($this->set[$container]);
    }

    public function setArray($container, $array)
    {
        $array = array_dot($array);
        
        foreach ($array as $key => $value)
        {
            array_set($this->set[$container], $key, $array[$key]);
        }
    }
    
    public function addArray($container, $array)
    {
        $this->set[$container] = array_merge_recursive($this->set[$container], $array);
    }

    public function dump($set)
    {
        switch ($set)
        {
            case 'json' :

                if (!empty($this->set[$set]))
                {
                    return self::printJson($this->set[$set]);
                }

            case 'styles' :

                $type = 'style';

                break;

            default :

                $type = 'script';
        }

        $content = array();

        foreach (array_flatten($this->set[$set]) as $path)
        {
            if ($this->inline && file_exists(public_path($path)))
            {
                $inline = File::get(public_path($path));

                if ($replace = $this->embeddedAssetReplace($this->getPath($path)))
                {
                    $inline = preg_replace('/url\(\'?"?(?!(|\'|")http)(?!(|\'|")data)/', $replace, $inline);
                }

                $content[] = $this->wrap($type, $inline);
            }
            else
            {
                $content[] = HTML::$type($path);
            }
        }

        return implode(PHP_EOL, $content);
    }

    public function embeddedAssetReplace($path)
    {
        $replace = Config::get('assets::config.replace-embedded-assets-on-styles');

        if ($replace instanceof Closure)
        {
            return $replace($path);
        }

        if (!$replace)
        {
            return false;
        }

        return '$0'.url($path).'/';
    }

    public static function printJson($array)
    {
        $json = json_encode($array);

        return "<script type=\"text/javascript\">/* <![CDATA[ */ var handover = $json; /* ]]> */</script>";
    }

    protected function event($set)
    {
        switch ($set)
        {
            case 'styles' :
                return 'Print styles';

            case 'header' :
                return 'Print scripts';

            case 'json' :
                return 'Print footer scripts';

            case 'footer' :
                return 'Print footer scripts';
        }
    }
    
    protected function clear()
    {
        foreach (array_keys($this->set) as $set)
        {
            $this->set = array();
        }
    }
    
    protected function flatten($internal = false)
    {
        $flatten = array();
        foreach ((array)$this->set as $set => $asset_arr)
        {
            $assets = array_flatten($asset_arr);

            $flatten[$set] = !$internal ? $assets : array_filter($assets, function($asset){

                return !preg_match('/^(\/\/|https?:\/\/)/', $asset);
            });
        }

        return $flatten;
    }

    public function getPath($path)
    {
        if (!preg_match('/^(\/\/|https?:\/\/)/', $path))
        {
            $path = explode('/', $path);
            if (count($path) > 1)
            {
                array_pop($path);
                $path = implode('/', $path);
            }
        }

        return $path;
    }

    public function getSet($key)
    {
        return isset($this->set[$key]) ? $this->set[$key] : false;
    }

    protected function wrap($type, $content)
    {
        switch ($type)
        {
            case 'style' :
                return '<style type="text/css" media="all">'.$content.'</style>';

            default :
                return '<script type="text/javascript">'.$content.'</script>';
        }
    }
}