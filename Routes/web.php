<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Vendeya\AuthController;
use App\Http\Controllers\Vendeya\PosController;

Route::get('/', function () {
    return redirect()->route('vendeya.login');
});

Route::prefix('vendeya')->name('vendeya.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('vendeya.auth')->group(function () {
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::get('/api/products', [PosController::class, 'getProducts'])->name('api.products');
        Route::get('/api/customers', [PosController::class, 'getCustomers'])->name('api.customers');
        Route::post('/customer/create', [PosController::class, 'createCustomer'])->name('customer.create');
        Route::post('/api/customers/create', [PosController::class, 'createCustomer'])->name('api.customers.create');
        Route::post('/api/sales', [PosController::class, 'createSale'])->name('api.sales.create');
        Route::post('/sale/create', [PosController::class, 'createSale'])->name('sale.create');
        
        Route::get('/api/test/series', [PosController::class, 'testSeries'])->name('api.test.series');
        
        Route::get('/cash/status', [PosController::class, 'checkCashStatus'])->name('cash.status');
        Route::post('/cash/open', [PosController::class, 'openCash'])->name('cash.open');
        Route::post('/cash/close', [PosController::class, 'closeCash'])->name('cash.close');
        Route::get('/cash/records', [PosController::class, 'getCashRecords'])->name('cash.records');
        
        Route::get('/api/document/{externalId}', [PosController::class, 'getDocumentByExternalId'])->name('api.document');
        Route::post('/api/lookup/document', [PosController::class, 'lookupDocument'])->name('api.lookup.document');
        
        Route::get('/test/create-voucher', function() {
            $controller = app()->make(\App\Http\Controllers\Vendeya\PosController::class);
            $request = new \Illuminate\Http\Request();
            $request->replace([
                'total' => 12.00,
                'customer_id' => 4,
                'series' => 'V001',
                'date_of_issue' => date('Y-m-d'),
                'time_of_issue' => date('H:i:s')
            ]);
            return $controller->createValeSale($request);
        })->name('test.create.voucher');
        
        Route::get('/force-voucher/{total?}', function($total = 15) {
            if (app()->environment('production')) {
                abort(404);
            }
            try {
                $lastNum = DB::connection('mysql_tenancy')->table('vouchers')
                    ->where('series', 'V001')
                    ->max('number') ?? 0;
                $newNumber = $lastNum + 1;

                $now = date('Y-m-d H:i:s');
                $externalId = uniqid('FORCE_', true);
                $establishment = json_encode(['id' => 1, 'name' => 'Establishment']);
                $customer = json_encode(['name' => 'Cliente Forzado', 'number' => '00000000']);

                $id = DB::connection('mysql_tenancy')->table('vouchers')->insertGetId([
                    'user_id' => 1,
                    'external_id' => $externalId,
                    'establishment_id' => 1,
                    'establishment' => $establishment,
                    'soap_type_id' => '01',
                    'state_type_id' => '01',
                    'series' => 'V001',
                    'number' => $newNumber,
                    'date_of_issue' => date('Y-m-d'),
                    'time_of_issue' => date('H:i:s'),
                    'customer_id' => 4,
                    'customer' => $customer,
                    'currency_type_id' => 'PEN',
                    'exchange_rate_sale' => 1,
                    'total' => $total,
                    'total_spent' => 0,
                    'total_balance' => $total,
                    'active' => 1,
                    'paid' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return "Voucher FORZADO creado! ID: $id, V001-$newNumber, Total: S/ $total.";
            } catch (\Exception $e) {
                return "Error: " . $e->getMessage();
            }
        });
        
        Route::get('/api/vouchers/balance/{doc}', [PosController::class, 'getVoucherBalance'])->name('api.vouchers.balance');
        Route::post('/api/vouchers/discount', [PosController::class, 'discountVoucher'])->name('api.vouchers.discount');
        Route::post('/api/vouchers/create', [PosController::class, 'createSale'])->name('api.vouchers.create');
    });
}); 