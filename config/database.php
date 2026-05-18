<?php

require_once __DIR__ . '/../functions/functions.php';

return [
    'host'     => env('DB_HOST', 'casestudy'),
    'dbname'   => env('DB_NAME', 'pos_db'),
    'user'     => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'port'     => (int) env('DB_PORT', 3306),
    'charset'  => 'utf8mb4',
];
