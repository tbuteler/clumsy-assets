<?php
namespace Clumsy\Assets\Support\Types;

class GoogleFont extends Style
{
    protected $fonts;

    protected $families;

    protected $options;

    public function __construct($attributes)
    {
        parent::__construct($attributes);

        $this->families = array();

        foreach ((array)$this->fonts as $font => $weights) {
            if (is_numeric($font)) {
                $font = $weights;
                $weights = array();
            }

            $font = urlencode($font);

            if (!$weights || !is_array($weights)) {
                $weights = array(400);
            }

            $weights = implode(',', array_map('urlencode', $weights));

            $this->families[] = "{$font}:{$weights}";
        }
    }

    public function getPath()
    {
        $this->families = implode('|', $this->families);

        if ($this->options) {
            $this->options = '&'.http_build_query($this->options);
        }

        return "//fonts.googleapis.com/css?family={$this->families}{$this->options}";
    }
}