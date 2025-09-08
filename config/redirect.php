<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Type
    |--------------------------------------------------------------------------
    |
    | This option defines whether to use file or database storage for redirects.
    | Options: 'file', 'database'
    |
    */

    'storage' => env('REDIRECTS_STORAGE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Redirects File Path
    |--------------------------------------------------------------------------
    |
    | The path where redirect definitions will be stored when using file storage.
    | Only used when storage is set to 'file'.
    |
    */

    'file_path' => storage_path('statamic/redirects/redirects.yaml'),

    /*
    |--------------------------------------------------------------------------
    | Database Table
    |--------------------------------------------------------------------------
    |
    | The database table name where redirects will be stored.
    | Only used when storage is set to 'database'.
    |
    */

    'table' => 'redirects',

    /*
    |--------------------------------------------------------------------------
    | Status Codes
    |--------------------------------------------------------------------------
    |
    | Available status codes that can be used for redirects.
    |
    */

    'status_codes' => [
        301 => '301 - Permanent',
        302 => '302 - Temporary',
        307 => '307 - Temporary (Preserve Method)',
        308 => '308 - Permanent (Preserve Method)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Enable caching for better performance.
    |
    */

    'cache' => [
        'enabled' => true,
        'expiry_time' => 60, // Minutes
    ],

];
