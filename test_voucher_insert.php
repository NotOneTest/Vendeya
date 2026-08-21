<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $pdo = new \PDO("mysql:host=127.0.0.1;port=3306;dbname=tenancy_miempresa;charset=utf8", "root", "");
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    
    echo "Connection successful\n";
    
    // Get establishment
    $stmt = $pdo->query("SELECT id, name FROM establishments LIMIT 1");
    $estRow = $stmt->fetch(\PDO::FETCH_ASSOC);
    $establishment = json_encode(['id' => $estRow['id'] ?? 1, 'name' => $estRow['name'] ?? 'Establishment']);
    
    // Get last voucher number
    $stmt = $pdo->query("SELECT COALESCE(MAX(number),0) as last_num FROM vouchers WHERE series = 'V001'");
    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
    $newNumber = $row['last_num'] + 1;
    
    echo "Last voucher number: " . $row['last_num'] . ", new: $newNumber\n";
    
    $externalId = uniqid('', true);
    $now = date('Y-m-d H:i:s');
    $customer = json_encode(['name' => 'Cliente Test', 'number' => '00000000']);
    $total = 12.00;
    
    $sql = "INSERT INTO vouchers (user_id, external_id, establishment_id, establishment, soap_type_id, state_type_id, series, number, date_of_issue, time_of_issue, customer_id, customer, currency_type_id, exchange_rate_sale, total, total_spent, total_balance, active, paid, created_at, updated_at) VALUES (1, ?, ?, ?, '01', '01', ?, ?, ?, ?, ?, ?, 'PEN', 1, ?, 0, ?, 1, 0, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    echo "Executing insert...\n";
    $stmt->execute([
        $externalId,
        $establishment,
        'V001',
        $newNumber,
        date('Y-m-d'),
        date('H:i:s'),
        4,
        $customer,
        $total,
        $total,
        $now,
        $now
    ]);
    
    echo "Voucher inserted! ID: " . $pdo->lastInsertId() . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
