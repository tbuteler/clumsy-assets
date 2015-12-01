<?php

namespace Clumsy\Assets\Support\Types;

class Json extends ArrayableAsset
{
    public function __toString()
    {
        $json = $this->toJson();
        return "<script type=\"text/javascript\">/* <![CDATA[ */ var {$this->key} = $json; /* ]]> */</script>";
    }
}
