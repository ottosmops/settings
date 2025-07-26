<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table where settings will be stored.
    |
    */
    'table' => env('SETTINGS_TABLE', 'settings'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings cache configuration. You can disable caching by setting
    | 'enabled' to false or customize the cache key prefix.
    |
    */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'key_prefix' => env('SETTINGS_CACHE_PREFIX', 'settings'),
        'ttl' => env('SETTINGS_CACHE_TTL', null), // null = forever
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings that will be created if they don't exist.
    | Format: 'key' => ['value' => 'default_value', 'type' => 'string', ...]
    |
    */
    'defaults' => [
        // Example:
        // 'app_name' => [
        //     'value' => 'My Application',
        //     'type' => 'string',
        //     'description' => 'Application name',
        //     'editable' => true,
        // ],
    ],
];
