<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$api = new App\Services\VendeyaApiService();
$response = $api->checkCashStatus();

echo "API Response:\n";
print_r($response);

echo "\n\nChecking config:\n";
echo "base_url: " . config('vendeya.base_url') . "\n";
echo "token: " . config('vendeya.token') . "\n";