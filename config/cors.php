<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration — SPEC §15
    |--------------------------------------------------------------------------
    | CORS hanya diizinkan untuk endpoint publik ke COMPRO_URL
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter([
        config('destinara.compro_url', env('COMPRO_URL', 'https://destinara.my.id')),
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'http://localhost:3000',
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
