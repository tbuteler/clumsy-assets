<?php

use Illuminate\Support\Facades\Config;

/*
 |--------------------------------------------------------------------------
 | Asset loader settings
 |--------------------------------------------------------------------------
 |
 |
 */

return array(

    /*
     |--------------------------------------------------------------------------
     | Fail silently
     |--------------------------------------------------------------------------
     |
     | Whether to throw an exception for assets which are enqueued but not
     | found. By default, it will throw exceptions for apps in debug mode.
     |
     */

    'silent' => !Config::get('app.debug'),

    /*
     |--------------------------------------------------------------------------
     | Inline output
     |--------------------------------------------------------------------------
     |
     | Instead of printing asset paths, print the entire asset's content inline.
     | Useful when there are few assets and/or a need to reduce server requests.
     |
     */

    'inline' => false,

    /*
     |--------------------------------------------------------------------------
     | Replace embedded assets on styles
     |--------------------------------------------------------------------------
     |
     | When using inline output of assets, should the package attempt to replace
     | embedded assets (i.e. "url('/path/to/css/image.png')") in your CSS files?
     | This can be a boolean or a callback which will be used as the replacement
     | function.
     |
     */

    'replace-embedded-assets-on-styles' => true,
);
