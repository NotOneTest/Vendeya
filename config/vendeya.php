<?php

return [
    'name' => 'Vendeya',
    'base_url' => env('VENDEYA_API_URL', 'https://factreadylite.fe-estudioscreativos.com'),
    'token' => env('VENDEYA_API_TOKEN'),
    'company' => env('VENDEYA_COMPANY', 'factreadylite'),
    'timeout' => env('VENDEYA_TIMEOUT', 30),
    'base_path' => '',
    'apiperu_token' => env('APIPERU_TOKEN', ''),
];
