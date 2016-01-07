<?php

namespace Clumsy\Assets\Support\Types;

class Typekit extends Style
{
    public function __toString()
    {
        return "<script src=\"https://use.typekit.net/{$this->kit_id}.js\"></script><script>try{Typekit.load({ async: true });}catch(e){}</script>";
    }
}
