<?php namespace Clumsy\Assets;

class Asset {

    public function __construct()
    {
        $this->container = new Container;
    }

	protected function on($set, $asset, $priority)
	{
		$this->container->add($set, $asset, $priority);
	}

    public function register($set, $key, $path, $v = '', $reqs = false)
    {
        return $this->container->register($set, $key, $path, $v, $reqs);
    }

    public function registerBatch($assets)
    {
    	$default = array(
    		'set'  => false,
    		'key'  => false,
    		'path' => false,
    		'v'	   => '',
    		'reqs' => false,
    	);

		foreach ($assets as $asset)
		{
			$asset = array_merge($default, (array)$asset);
	        extract($asset);

	        $this->register($set, $key, $path, $v, $reqs);
		}
    }
	
	public function enqueueNew($set, $key, $path, $v = '', $reqs = false, $priority = 25)
	{
        if ($this->register($set, $key, $path, $v, $reqs))
        {
            $this->enqueue($key, $priority);
        }

        return false;
	}

	public function enqueue($asset, $priority = 25)
	{
		$assets = $this->container->assets;

		if (!isset($assets[$asset]))
        {
			if (\Config::get('assets::config.silent'))
            {
                return false; // Fail silently, unless debug is on
            }
            
            throw new \Exception("Unknown asset $asset.");            
		}
		
		if (isset($assets[$asset]['req']))
        {	
			foreach((array)$assets[$asset]['req'] as $req)
            {
				$this->enqueue($req, $priority);
			}
		}

		$path = $assets[$asset]['path'];

		$v = isset($assets[$asset]['v']) ? $assets[$asset]['v'] : null;

		$this->on($assets[$asset]['set'], array('key' => $asset, 'path' => $path, 'v' => $v), $priority);
	}

	public function style($path)
	{
		return \HTML::style($path);
	}

	public function json($id, $array)
	{
        $container = $this->container->addArray('json', array($id => $array));
    }

	public function unique($id, \Closure $closure)
	{
        $container = $this->container;
        
        if (!in_array($id, $container->unique))
        {
            $this->container->unique[] = $id;

            call_user_func($closure);

            return true;
        }
        
        return false;
	}
	
	public function font($name, $weights = false)
	{
		if (!$weights || !is_array($weights))
        {
			$weights = array(400);
		}
		$weights = implode(',', $weights);

        $this->enqueueNew('styles', "font.$name", "//fonts.googleapis.com/css?family=$name:$weights", null, null, 50);
	}
}