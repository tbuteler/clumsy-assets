<?php
namespace Clumsy\Assets\Support\Types;

class Script extends Asset
{
    protected $method = 'script';

    public function inline()
    {
        return '<script type="text/javascript">'.$this->content().'</script>';
    }
}