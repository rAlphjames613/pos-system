<?php

require_once __DIR__ . '/../functions/functions.php';

return [
    'app_id'  => env('PUSHER_APP_ID'),
    'key'     => env('PUSHER_KEY'),
    'secret'  => env('PUSHER_SECRET'),
    'cluster' => env('PUSHER_CLUSTER', 'ap1'),
];
