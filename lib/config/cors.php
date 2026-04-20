<?php
return [
    'paths' => ['api/*', 'v1/*','sanctum/csrf-cookie'],

    'allowed_origins' => [
        '*'
    ],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];