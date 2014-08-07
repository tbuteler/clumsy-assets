<?php namespace Clumsy\Assets;

use Event;

class Container {

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
        $this->assets = \Config::get('assets::assets');

		foreach (array_keys($this->set) as $set)
        {
			$container = $this;

			Event::listen($this->event($set), function() use ($container, $set)
            {
				return $container->dump($set);

			}, 25);
		}
	}

	public function register($set, $key, $path, $v = false, $reqs = false)
	{
        if (!isset($this->assets[$key]))
        {
            $this->assets[$key] = array(
    			'set'	=> $set,
    			'path'	=> $path,
            );

            if ($reqs)
            {
                $this->assets[$key]['req'] = (array)$reqs;
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

		$suffix = isset($asset['v']) && $asset['v'] != '' ? '?v=' . (string)$asset['v'] : '';

		$this->set[$container][$priority] = array_merge($this->set[$container][$priority], array($key => $path.$suffix));

		krsort($this->set[$container]);
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
                    return self::printJson(\Config::get('assets::config.json_variable'), $this->set[$set]);
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
            $content[] = \HTML::$type($path);
		}

        return implode(PHP_EOL, $content);
	}

    public static function printJson($id, $array)
    {
        $json = json_encode($array);

        return "<script type=\"text/javascript\">/* <![CDATA[ */ var $id = $json; /* ]]> */</script>";
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

    public function getSet($key)
    {
        return isset($this->set[$key]) ? $this->set[$key] : false;
    }
}