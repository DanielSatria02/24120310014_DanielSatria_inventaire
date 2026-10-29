<?php

/*
 * CORS is locked down: this app is server-rendered and has no cross-origin API,
 * so no path is opened to other origins. If an API is added later, list its
 * paths here and keep `allowed_origins` limited to the origins you trust.
 */
return [
    'paths' => [],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],

    'allowed_origins' => [env('APP_URL', 'http://localhost')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
