<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $controller = new \App\Http\Controllers\Vendeya\PosController();
    $request = new \Illuminate\Http\Request();
    $request->replace([
        'document_type' => 'vale',
        'total' => 28.00,
        'customer_id' => 4,
        'serie' => 'V001',
        'date_of_issue' => date('Y-m-d'),
        'time_of_issue' => date('H:i:s'),
        'payment_method' => '05'
    ]);
    
    $response = $controller->createSale($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "<h3>Result:</h3>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($data['success']) && $data['success']) {
        echo "<p style='color:green'>SUCCESS! Voucher created.</p>";
        echo "<p><a href='http://miempresa.pro-8-2026.test/vouchers/receipts'>View vouchers</a></p>";
    } else {
        echo "<p style='color:red'>FAILED: " . ($data['message'] ?? 'Unknown error') . "</p>";
        // Show log
        $log = file_get_contents(__DIR__ . '/../storage/logs/laravel.log');
        $lines = explode("\n", $log);
        $lastLines = array_slice($lines, -20);
        echo "<h4>Last log entries:</h4>";
        echo "<pre>" . implode("\n", $lastLines) . "</pre>";
    }
    
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage();
    echo "<br>Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
