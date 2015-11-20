<?php

namespace Clumsy\Assets\Support\Types;

class Style extends Asset
{
    protected $method = 'style';

    public function inline()
    {
        return '<style type="text/css" media="all">'.$this->content().'</style>';
    }
}