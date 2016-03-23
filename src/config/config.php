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
     | Javascript variable name
     |--------------------------------------------------------------------------
     |
     | The name of the variable which will contain the JSON data when passing
     | PHP data via the Asset::json method
     |
     */

     'json-variable' => 'handover',

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
     | Elixir versioning default
     |--------------------------------------------------------------------------
     |
     | Should the asset loader attempt to load the versioned path of the asset,
     | with the hash created by Laravel's Elixir? This can safely be true for
     | all assets, even if they don't have proper revisions -- the loader falls
     | back to the "raw" path if no elixir version is available.
     |
     | This setting can be overridden by each individual asset's "elixir"
     | property.
     |
     */

    'elixir' => false,

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
