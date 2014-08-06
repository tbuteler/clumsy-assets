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
     | Global variable name for JSON data
     |--------------------------------------------------------------------------
     |
     | All data passed via the json method is consolidated in one JSON object,
     | which is assigned to a global Javascript variable.
     |
     */

    'json_variable' => 'json',
);