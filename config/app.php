<?php

require_once __DIR__ . '/../functions/functions.php';

return [
    'name'             => env('APP_NAME', 'POS System'),
    'env'              => env('APP_ENV', 'development'),
    'debug'            => env('APP_DEBUG', true),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 3600),
];
