<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'public/img/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:5173'], // or ['http://localhost:3000'] if you want it specific

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // fixed typo here

];
