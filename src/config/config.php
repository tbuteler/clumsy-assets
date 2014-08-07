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
);