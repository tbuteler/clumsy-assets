<?php

/*
 |--------------------------------------------------------------------------
 | Asset loader settings
 |--------------------------------------------------------------------------
 |
 |
 */

return [

    /*
     |--------------------------------------------------------------------------
     | Fail silently
     |--------------------------------------------------------------------------
     |
     | Whether to throw an exception for assets which are enqueued but not
     | found. By default, it will throw exceptions for apps in debug mode.
     |
     */

    'silent' => !config('app.debug'),

    /*
     |--------------------------------------------------------------------------
     | Inline output
     |--------------------------------------------------------------------------
     |
     | Instead of printing asset paths, print the entire asset's content inline.
     | Useful when there are few assets and/or a need to reduce server requests,
     | and especially useful when caching.
     |
     | This setting can be overridden by each individual asset's "inline"
     | property.
     |
     */

    'inline' => false,

    /*
     |--------------------------------------------------------------------------
     | Replace embedded assets
     |--------------------------------------------------------------------------
     |
     | When using inline output of assets, should the package attempt to replace
     | embedded assets (i.e. "url('/path/to/css/image.png')") in your files?
     | This can be a boolean or a callback which will be used as the replacement
     | function.
     |
     */

    'replace-embedded-assets' => true,

    /*
     |--------------------------------------------------------------------------
     | Default web font provider
     |--------------------------------------------------------------------------
     |
     | When enqueing fonts, which provider should the loader use by default.
     |
     | Supported: "google"
     |
     */

    'font-provider' => 'google',
];
