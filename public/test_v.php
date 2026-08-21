<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $controller = new \App\Http\Controllers\Vendeya\PosController();
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'document_type' => 'vale',
        'total' => 25.00,
        'customer_id' => 4,
        'serie' => 'V001',
        'date_of_issue' => date('Y-m-d'),
        'time_of_issue' => date('H:i:s')
    ]);
    
    $response = $controller->createSale($request);
    $data = json_decode($response->getContent(), true);
    
    if ($data['success']) {
        echo "SUCCESS! Voucher creado. ID: " . ($data['data']['id'] ?? 'N/A') . "<br>";
        echo "Ver en: <a href='http://miempresa.pro-8-2026.test/vouchers/receipts'>/vouchers/receipts</a>";
    } else {
        echo "ERROR: " . ($data['message'] ?? 'Unknown') . "<br>";
        echo "LOG: " . file_get_contents(storage_path('logs/laravel.log'));
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage();
}
