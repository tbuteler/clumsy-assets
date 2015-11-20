<?php

namespace Clumsy\Assets\Support\Types;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class ArrayableAsset implements Arrayable, Jsonable
{
    protected $key;

    protected $contents = [];

    public function __construct($key, $contents)
    {
        $this->key = $key;
        $this->contents = $contents;
    }

    public function getValue($key)
    {
        return $this->contents[$key];
    }

    public function setValue($key, $value)
    {
        $this->contents[$key] = $value;
    }

    public function getContents()
    {
        return $this->contents;
    }

    public function setContents(array $contents = [])
    {
        $this->contents = $contents;
    }

    /**
     * Convert the asset to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the asset to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->contents;
    }

    public function __get($key)
    {
        return $this->getValue($key);
    }

    /**
     * Dynamically set attributes on the asset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setValue($key, $value);
    }

    /**
     * Determine if an attribute exists on the asset.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->contents[$key]);
    }

    /**
     * Unset an attribute on the asset.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->contents[$key]);
    }
}