<?php

namespace App\Http\Controllers\Vendeya;

use App\Http\Controllers\Controller;
use App\Services\VendeyaApiService;
use App\Services\MiEmpresaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class PosController extends Controller
{
    protected VendeyaApiService $api;
    protected MiEmpresaApiService $miEmpresaApi;

    public function __construct(VendeyaApiService $api, MiEmpresaApiService $miEmpresaApi)
    {
        $this->api = $api;
        $this->miEmpresaApi = $miEmpresaApi;
    }

    public function index()
    {
        $cashStatus = $this->api->checkCashStatus();
        
        Log::info('Cash Status Check:', $cashStatus);
        
        $cashOpened = false;
        
        // La API retorna los datos en 'data' incluso si success es false
        $data = $cashStatus['data'] ?? null;
        
        if ($data && is_array($data) && !empty($data)) {
            Log::info('Cash Status Data:', ['data_count' => count($data), 'first_item' => $data[0] ?? null]);
            
            // Buscar si hay alguna caja con state = true (abierta)
            foreach ($data as $cash) {
                if (isset($cash['state']) && $cash['state'] === true) {
                    $cashOpened = true;
                    Log::info('Cash opened found:', $cash);
                    break;
                }
            }
        }

        Session::put('cash_opened', $cashOpened);

        $fullApiUrl = config('vendeya.base_url', 'https://factreadylite.fe-estudioscreativos.com');
        $baseApiUrl = preg_replace('#/api$#', '', $fullApiUrl);
        $apiDomain = str_replace(['https://', 'http://'], '', $baseApiUrl);
        
        $logo = $baseApiUrl . '/storage/uploads/logos/logo_44444444444.png';
        
        $user = Session::get('vendeya_user');
        
        // Obtener productos para extraer categorías únicas
        $tempProductsResponse = $this->api->getProducts();
        $tempProducts = $tempProductsResponse['data'] ?? [];
        
        // Extraer categorías únicas de los productos usando full_description
        $categoryMap = [];
        foreach ($tempProducts as $item) {
            $fullDesc = $item['full_description'] ?? '';
            $parts = explode(' - ', $fullDesc);
            if (count($parts) >= 3) {
                $categoryName = trim(end($parts));
                $catId = $item['category_id'] ?? null;
                if ($catId && $categoryName && !isset($categoryMap[$catId])) {
                    $categoryMap[$catId] = $categoryName;
                }
            }
        }
        
        $productsResponse = $this->api->getProducts();
        $products = $this->normalizeProductsFromApi($productsResponse);
        
        // Mapear category_id al nombre de categoría
        foreach ($products as &$product) {
            $catId = $product['category_id'] ?? null;
            $product['category'] = $categoryMap[$catId] ?? 'Sin categoría';
        }
        unset($product);
        
        Log::info('Products mapped sample: ' . json_encode(array_slice($products, 0, 3)));
        
        if (empty($products)) {
            $products = $this->getSampleProducts();
        }
        
        // Extraer categorías de los productos
        $allCategories = $this->extractCategories($products);
        
        // Si no hay categorías, usar predefined
        if (empty($allCategories)) {
            $allCategories = ['Bebidas', 'Escritorio', 'Perfumería'];
        }
        
        sort($allCategories);
        array_unshift($allCategories, 'Todos');
        
        $customersResponse = $this->api->getCustomers();
        $customers = $this->normalizeCustomersFromApi($customersResponse);
        
        if (empty($customers)) {
            $customers = $this->getSampleCustomers();
        }

        $seriesResponse = $this->api->getSeries();
        $series = $seriesResponse['data'] ?? [];

        $establishmentsResponse = $this->api->getEstablishments();
        $establishments = $establishmentsResponse['data'] ?? [];

        return view('vendeya.pos.dashboard', compact('user', 'products', 'allCategories', 'customers', 'cashOpened', 'apiDomain', 'logo', 'series', 'establishments'));
    }

    public function openCash(Request $request)
    {
        $amount = $request->validate([
            'initial_amount' => 'required|numeric|min:0',
        ]);

        $checkResponse = $this->api->checkCashStatus();
        
        $isAlreadyOpen = false;
        if (isset($checkResponse['success']) && $checkResponse['success']) {
            if (isset($checkResponse['data'])) {
                if (is_array($checkResponse['data']) && ($checkResponse['data']['status'] ?? '') === 'open') {
                    $isAlreadyOpen = true;
                } elseif ($checkResponse['data'] === true) {
                    $isAlreadyOpen = true;
                }
            }
        }
        
        if ($isAlreadyOpen) {
            Session::put('cash_opened', true);
            return response()->json([
                'success' => true,
                'message' => 'La caja ya está abierta',
            ]);
        }

        $userId = Session::get('vendeya_user.id', 1);
        $response = $this->api->openCash($amount['initial_amount'], $userId);

        Log::info('OpenCash API Response', [
            'user_id' => $userId,
            'amount' => $amount['initial_amount'],
            'response' => $response
        ]);

        if (isset($response['success']) && $response['success']) {
            Session::put('cash_opened', true);
            return response()->json([
                'success' => true,
                'message' => 'Caja abierta exitosamente',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['error'] ?? 'Error al abrir caja en el facturador',
        ], 400);
    }

    public function checkCashStatus()
    {
        $response = $this->api->checkCashStatus();
        return response()->json($response);
    }

public function closeCash(Request $request)
    {
        $cashId = $request->input('cash_id');
        
        if (!$cashId) {
            $recordsResponse = $this->api->getCashRecords();
            
            Log::info('Cash records response', $recordsResponse);
            
            if (isset($recordsResponse['data']) && is_array($recordsResponse['data'])) {
                $records = $recordsResponse['data'];
                foreach ($records as $record) {
                    $dateClosed = $record['date_closed'] ?? null;
                    $state = $record['state'] ?? false;
                    
                    if (empty($dateClosed) || $state === true) {
                        $cashId = $record['id'];
                        Log::info('Found open cash', ['id' => $cashId]);
                        break;
                    }
                }
            }
        }
        
        if (!$cashId) {
            return response()->json([
                'success' => false,
                'message' => 'No hay caja abierta para cerrar',
            ], 400);
        }
        
        $response = $this->api->closeCashById($cashId);

        Log::info('Close cash response', $response);
        
        $reportUrl = null;
        $reportData = null;
        
        if (isset($response['success']) && $response['success']) {
            $reportResponse = $this->api->getCashGeneralReport(['cash_id' => $cashId]);
            Log::info('Cash report response', $reportResponse);
            
            if (isset($reportResponse['success']) && $reportResponse['success']) {
                $reportData = $reportResponse['data'] ?? null;
                $fullApiUrl = config('vendeya.base_url');
                $baseUrl = preg_replace('#/api$#', '', $fullApiUrl);
                $reportUrl = $baseUrl . '/cash/print closing/' . $cashId . '/a4';
            }
        }

        if (isset($response['success']) && $response['success']) {
            Session::put('cash_opened', false);
            return response()->json([
                'success' => true,
                'message' => 'Caja cerrada exitosamente',
                'report_url' => $reportUrl,
                'report_data' => $reportData,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['error'] ?? 'Error al cerrar caja en el facturador',
        ], 400);
    }

    public function getCashRecords()
    {
        $response = $this->api->getCashRecords();
        return response()->json($response);
    }

    public function testSeries()
    {
        $response = $this->api->getSeries();
        return response()->json($response);
    }

    public function getProducts(Request $request)
    {
        $category = $request->get('category', 'Todos');
        $productsResponse = $this->api->getProducts();
        $products = $this->normalizeProductsFromApi($productsResponse);

        if (empty($products)) {
            $products = $this->getSampleProducts();
        }

        if ($category !== 'Todos') {
            $products = array_filter($products, fn($p) => $p['category'] === $category);
        }

        return response()->json(['success' => true, 'data' => array_values($products)]);
    }

    public function getCustomers()
    {
        $response = $this->api->getCustomers();
        $customers = $this->normalizeCustomersFromApi($response);
        
        if (empty($customers)) {
            return response()->json([
                'success' => true,
                'data' => $this->getSampleCustomers(),
                'fallback' => true
            ]);
        }
        
        return response()->json(['success' => true, 'data' => $customers]);
    }

    private function normalizeCustomersFromApi(array $response): array
    {
        $data = $response['data'] ?? [];
        if (empty($data)) {
            return [];
        }

        $customers = [];
        foreach ($data as $item) {
            $customers[] = [
                'id' => $item['id'] ?? null,
                'name' => $item['name'] ?? $item['description'] ?? $item['razon_social'] ?? 'Sin nombre',
                'number' => $item['number'] ?? $item['document_number'] ?? $item['num_doc'] ?? '00000000',
            ];
        }

        return $customers;
    }

    private function normalizeProductsFromApi(array $response): array
    {
        $data = $response['data'] ?? [];
        if (empty($data)) {
            return [];
        }

$products = [];
        foreach ($data as $item) {
            $price = $item['sale_unit_price'] ?? 0;
            if (is_string($price)) {
                $price = floatval(str_replace(['S/', ' '], '', $price));
            }
            
            // Save category_id for mapping later
            $categoryId = $item['category_id'] ?? null;
            
            $name = $item['description'] ?? $item['name'] ?? 'Sin nombre';
            $isFuel = $this->isFuelProduct($name);
            $fuelPrice = $isFuel ? $price : null;
            
            $products[] = [
                'id' => $item['id'] ?? null,
                'name' => $name,
                'code' => $item['barcode'] ?? $item['internal_id'] ?? '',
                'price' => $price,
                'category_id' => $categoryId,
                'category' => 'Sin categoría', // Will be mapped later
                'image' => $item['image_url_small'] ?? $item['image_url'] ?? '',
                'is_fuel' => $isFuel,
                'fuel_price' => $fuelPrice,
            ];
        }
        
        return $products;
    }

    private function extractCategories(array $products): array
    {
        $categories = [];
        foreach ($products as $product) {
            $cat = $product['category'] ?? 'Sin categoría';
            if ($cat !== 'Sin categoría' && !empty($cat) && !in_array($cat, $categories)) {
                $categories[] = $cat;
            }
        }
        sort($categories);
        return $categories;
    }
    
    private function extractApiCategories(array $response): array
    {
        $categories = [];
        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $item) {
                $desc = $item['description'] ?? $item['name'] ?? null;
                if ($desc && !in_array($desc, $categories)) {
                    $categories[] = $desc;
                }
            }
        }
        return $categories;
    }

    public function createCustomer(Request $request)
    {
        $validated = $request->validate([
            'identity_document_type_id' => 'required',
            'number' => 'required',
            'name' => 'required',
        ]);

        $customerData = [
            'type' => 'customers',
            'identity_document_type_id' => $request->input('identity_document_type_id', '1'),
            'number' => $request->input('number'),
            'name' => $request->input('name'),
            'address' => $request->input('address', ''),
            'telephone' => $request->input('telephone', ''),
            'email' => $request->input('email', ''),
            'country_id' => 'PE',
            'perception_agent' => false,
            'percentage_perception' => 0,
        ];

        Log::info('Creating customer with data:', $customerData);
        
        $response = $this->api->createCustomer($customerData);
        
        Log::info('Create customer response:', $response);

        if (isset($response['success']) && $response['success']) {
            return response()->json([
                'success' => true,
                'msg' => 'Cliente registrado con éxito',
                'data' => $response['data'] ?? $customerData,
            ]);
        }

        $errorMsg = $response['error'] ?? $response['msg'] ?? 'Error al crear cliente';
        
        if (isset($response['errors'])) {
            $errorMsg = is_array($response['errors']) 
                ? implode(', ', array_map(function($e) { return is_array($e) ? implode(', ', $e) : $e; }, $response['errors'])) 
                : $response['errors'];
        }

        return response()->json([
            'success' => false,
            'msg' => $errorMsg,
            'errors' => $response,
        ], 400);
    }

    public function createSale(Request $request)
    {
        if (!Session::get('cash_opened')) {
            return response()->json([
                'success' => false,
                'message' => 'La caja está cerrada. Debe abrir caja antes de realizar ventas.',
            ], 400);
        }

        $data = $request->all();

        $documentType = $data['document_type'] ?? 'nv';
        $paymentMethod = $data['payment_method'] ?? '01';
        
        // Vale/Voucher detection
        $this->ensureAuthenticated();
        
        if ($documentType === 'vale' || $paymentMethod === '05') {
            Log::info('FORZANDO creación de voucher - payment_method: ' . $paymentMethod);
            return $this->createValeSale($data);
        }
        
        $tipoDocumentoMap = [
            'nv' => '80',
            'boleta' => '03',
            'factura' => '01',
        ];
        
        $tipoDocumento = $tipoDocumentoMap[$documentType] ?? '80';

        $now = now();
        $fechaEmision = $now->format('Y-m-d');
        $horaEmision = $now->format('H:i:s');
        $fechaVencimiento = $now->addDays(7)->format('Y-m-d');

        $customerId = $data['customer_id'] ?? 1;
        $customerData = $this->getCustomerData($customerId);
        
        $customerDocType = $customerData['tipo_documento'] ?? '1';
        $customerDocNumber = $customerData['numero'] ?? '00000000';
        $customerName = $customerData['nombre'] ?? 'CLIENTES - VARIOS';
        $customerAddress = $customerData['direccion'] ?? 'Av. Principal';
        $customerEmail = $customerData['email'] ?? '';
        
        if ($tipoDocumento === '01') {
            $customerDocType = '6';
            if (strlen($customerDocNumber) !== 11) {
                $customerDocNumber = '10414711225';
            }
        }
        
        $items = $data['items'] ?? [];
        $subtotal = 0;
        $igv = 0;
        $total = 0;
        
        $documentItems = [];
        
        $isNotaVenta = ($documentType === 'nv');
        
        foreach ($items as $item) {
            $quantity = floatval($item['quantity'] ?? 1);
            $unitPrice = floatval($item['price'] ?? 0);
            $totalItem = $quantity * $unitPrice;
            
            if ($isNotaVenta) {
                $baseGravada = $totalItem;
                $igvItem = 0;
            } else {
                $baseGravada = $totalItem / 1.18;
                $igvItem = $totalItem - $baseGravada;
            }
            
            $subtotal += $baseGravada;
            $igv += $igvItem;
            $total += $totalItem;
            
            $documentItems[] = [
                'item_id' => $item['id'] ?? 1,
                'item' => [
                    'id' => $item['id'] ?? 1,
                    'item_id' => $item['id'] ?? 1,
                    'name' => $item['name'] ?? 'Producto',
                    'full_description' => $item['name'] ?? 'Producto',
                    'description' => $item['name'] ?? 'Producto',
                    'currency_type_id' => 'PEN',
                    'internal_id' => 'P' . str_pad($item['id'] ?? 0, 4, '0', STR_PAD_LEFT),
                    'item_code' => '51121703',
                    'currency_type_symbol' => 'S/',
                    'unit_type_id' => $item['unit_type_id'] ?? 'NIU',
                    'sale_affectation_igv_type_id' => '10',
                    'purchase_affectation_igv_type_id' => '10',
                    'calculate_quantity' => false,
                    'has_igv' => !$isNotaVenta,
                    'is_set' => false,
                    'unit_price' => round($unitPrice, 2),
                    'sale_unit_price' => round($unitPrice, 2),
                ],
                'currency_type_id' => 'PEN',
                'affectation_igv_type_id' => '10',
                'system_isc_type_id' => null,
                'total_base_isc' => 0,
                'percentage_isc' => 0,
                'total_isc' => 0,
                'total_base_other_taxes' => 0,
                'percentage_other_taxes' => 0,
                'total_other_taxes' => 0,
                'total_base_igv' => round($baseGravada, 2),
                'percentage_igv' => $isNotaVenta ? 0 : 18,
                'quantity' => $quantity,
                'unit_value' => round($baseGravada / $quantity, 2),
                'price_type_id' => '01',
                'unit_price' => round($unitPrice, 2),
                'total_value' => round($baseGravada, 2),
                'total_igv' => round($igvItem, 2),
                'total_taxes' => round($igvItem, 2),
                'total' => round($totalItem, 2),
            ];
        }

        $plateNumber = $data['plate_number'] ?? null;
        
        if ($isNotaVenta) {
            $payload = [
                'document_type_id' => '80',
                'prefix' => $data['prefix'] ?? 'NV',
                'series_id' => (int) ($data['series_id'] ?? 10),
                'establishment_id' => (int) ($data['establishment_id'] ?? 1),
                'date_of_issue' => $fechaEmision,
                'time_of_issue' => $horaEmision,
                'customer_id' => $customerId,
                'currency_type_id' => 'PEN',
                'purchase_order' => null,
                'plate_number' => $plateNumber,
                'exchange_rate_sale' => 0,
                'operation_type_id' => null,
                'charges' => [],
                'discounts' => [],
                'attributes' => [],
                'guides' => [],
                'payments' => array_map(function($p) use ($fechaEmision) {
                    return [
                        'id' => null,
                        'payment' => round(floatval($p['monto'] ?? $total), 2),
                        'date_of_payment' => $fechaEmision,
                        'payment_method_type_id' => $p['codigo_metodo_pago'] ?? '01',
                        'reference' => '',
                        'amount' => round(floatval($p['monto'] ?? $total), 2),
                    ];
                }, $data['pagos'] ?? [['codigo_metodo_pago' => '01', 'monto' => round($total, 2)]]),
                'additional_information' => null,
                'seller_id' => (int) ($data['seller_id'] ?? 1),
                'actions' => [
                    'format_pdf' => 'a4'
                ],
                'apply_concurrency' => false,
                'type_period' => null,
                'quantity_period' => 0,
                'automatic_date_of_issue' => null,
                'enabled_concurrency' => false,
                'total_prepayment' => 0,
                'total_charge' => 0,
                'total_discount' => 0,
                'total_exportation' => 0,
                'total_free' => 0,
                'total_unaffected' => 0,
                'total_exonerated' => 0,
                'total_base_isc' => 0,
                'total_isc' => 0,
                'total_base_other_taxes' => 0,
                'total_other_taxes' => 0,
                'total' => round($total, 2),
                'total_igv' => round($igv, 2),
                'total_taxed' => round($subtotal, 2),
                'total_value' => round($subtotal, 2),
                'datos_del_cliente_o_receptor' => [
                    'codigo_tipo_documento_identidad' => '1',
                    'numero_documento' => '00000000',
                    'apellidos_y_nombres_o_razon_social' => 'CLIENTES - VARIOS',
                    'codigo_pais' => 'PE',
                    'ubigeo' => '',
                    'direccion' => 'Av. Principal',
                    'correo_electronico' => '',
                    'telefono' => '',
                ],
                'items' => $documentItems,
            ];
            
            Log::info('Vendeya Sale Payload', $payload);
            $response = $this->api->createDocumentStore($payload);
        } else {
            $serie = $data['serie'] ?? 'B001';
            
            $payload = [
                'serie_documento' => $serie,
                'numero_documento' => '#',
                'fecha_de_emision' => $fechaEmision,
                'hora_de_emision' => $horaEmision,
                'codigo_tipo_operacion' => '0101',
                'codigo_tipo_documento' => $tipoDocumento,
                'codigo_tipo_moneda' => 'PEN',
                'fecha_de_vencimiento' => $fechaVencimiento,
                'numero_orden_de_compra' => '',
                'numero_de_placa' => $plateNumber,
                'pagos' => array_map(function($p) use ($fechaEmision) {
                    return [
                        'fecha_de_emision' => $fechaEmision,
                        'codigo_metodo_pago' => $p['codigo_metodo_pago'] ?? '01',
                        'codigo_destino_pago' => '',
                        'referencia' => '',
                        'monto' => round(floatval($p['monto'] ?? $total), 2),
                    ];
                }, $data['pagos'] ?? [['codigo_metodo_pago' => '01', 'monto' => round($total, 2)]]),
                'codigo_vendedor' => 1,
                'datos_del_cliente_o_receptor' => [
                    'codigo_tipo_documento_identidad' => $customerDocType,
                    'numero_documento' => $customerDocNumber,
                    'apellidos_y_nombres_o_razon_social' => $customerName,
                    'codigo_pais' => 'PE',
                    'ubigeo' => '',
                    'direccion' => $customerAddress,
                    'correo_electronico' => $customerEmail,
                    'telefono' => '',
                ],
                'totales' => [
                    'total_exportacion' => 0,
                    'total_operaciones_gravadas' => round($subtotal, 2),
                    'total_operaciones_inafectas' => 0,
                    'total_operaciones_exoneradas' => 0,
                    'total_operaciones_gratuitas' => 0,
                    'total_igv' => round($igv, 2),
                    'total_impuestos' => round($igv, 2),
                    'total_valor' => round($subtotal, 2),
                    'total_venta' => round($total, 2),
                ],
                'items' => array_map(function($item) use ($isNotaVenta) {
                    return [
                        'codigo_interno' => $item['item']['internal_id'] ?? 'P0001',
                        'descripcion' => $item['item']['name'] ?? 'Producto',
                        'codigo_producto_sunat' => $item['item']['item_code'] ?? '51121703',
                        'unidad_de_medida' => $item['item']['unit_type_id'] ?? 'NIU',
                        'cantidad' => $item['quantity'],
                        'valor_unitario' => round($item['unit_value'], 2),
                        'codigo_tipo_precio' => $item['price_type_id'] ?? '01',
                        'precio_unitario' => round($item['unit_price'], 2),
                        'codigo_tipo_afectacion_igv' => $item['affectation_igv_type_id'] ?? '10',
                        'total_base_igv' => round($item['total_base_igv'], 2),
                        'porcentaje_igv' => $item['percentage_igv'] ?? 18,
                        'total_igv' => round($item['total_igv'], 2),
                        'total_impuestos' => round($item['total_taxes'], 2),
                        'total_valor_item' => round($item['total_value'], 2),
                        'total_item' => round($item['total'], 2),
                    ];
                }, $documentItems),
            ];
            
            Log::info('Vendeya Document Payload', $payload);
            $response = $this->api->createInvoiceDocument($payload);
        }

        Log::info('Vendeya Sale Response', $response);

        if (isset($response['success']) && $response['success']) {
            $documentId = $response['data']['number_full'] ?? $response['data']['number'] ?? 'NV01-' . rand(1, 999);
            $externalId = $response['data']['external_id'] ?? $response['data']['id'] ?? $response['data']['numero_documento'] ?? null;
            
            $documentData = [
                'document_id' => $documentId,
                'external_id' => $externalId,
                'total' => round($total, 2),
                'serie' => $data['serie'] ?? ($isNotaVenta ? 'NV01' : 'B001'),
                'tipo_documento' => $tipoDocumento,
                'date_of_issue' => $fechaEmision,
                'time_of_issue' => $horaEmision,
                'customer_name' => $customerName,
                'customer_number' => $customerDocNumber,
                'customer_address' => $customerAddress,
                'items' => array_map(function($item) {
                    return [
                        'description' => $item['item']['name'] ?? 'Producto',
                        'quantity' => $item['quantity'],
                        'unit_type_id' => $item['unit_type_id'] ?? 'NIU',
                        'unit_price' => $item['unit_price'],
                        'total' => $item['total'],
                    ];
                }, $documentItems),
                'total_value' => $subtotal,
                'total_igv' => $igv,
                'total' => $total,
                'paid' => floatval($data['paid'] ?? $total),
                'change' => max(0, floatval($data['paid'] ?? 0) - $total),
            ];
            
            Session::put('last_document_' . $externalId, $documentData);
            
            $cashId = $this->getOpenCashId();
            Log::info('createSale - cash ID resolved', ['cash_id' => $cashId, 'external_id' => $externalId, 'total' => $total]);

            if ($cashId) {
                try {
                    $this->api->associateDocumentToCash([
                        'cash_id' => $cashId,
                        'external_id' => $externalId,
                        'total' => round($total, 2),
                        'document_type' => $tipoDocumento,
                'serie' => $data['serie'] ?? ($isNotaVenta ? 'NV01' : 'B001'),
                        'number' => $documentId,
                    ]);
                    $this->syncCashIncome($cashId, $total);
                } catch (\Exception $e) {
                    Log::error('Error syncing cash income after sale', ['error' => $e->getMessage()]);
                }
            } else {
                Log::warning('No cash ID found for sale, skipping cash sync', ['external_id' => $externalId, 'total' => $total]);
            }
            
            $apiDomain = rtrim(preg_replace('#/api$#', '', config('vendeya.base_url', 'https://factreadylite.fe-estudioscreativos.com')), '/');
            $printPath = $isNotaVenta 
                ? '/sale-notes/print/' . $externalId . '/ticket'
                : '/print/document/' . $externalId . '/ticket';
            $printTicket = $apiDomain . $printPath;
            
            return response()->json([
                'success' => true,
                'message' => 'Venta realizada con éxito',
                'data' => [
                    'document_id' => $documentId,
                    'external_id' => $externalId,
                    'total' => round($total, 2),
                    'serie' => $data['serie'] ?? ($isNotaVenta ? 'NV01' : 'B001'),
                    'tipo_documento' => $tipoDocumento,
                    'document_data' => $documentData,
                    'print_ticket' => $printTicket,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['error'] ?? 'Error al crear el documento',
            'errors' => $response,
        ], 400);
    }

    private function getCustomerData(?int $customerId): array
    {
        if (!$customerId) {
            return [
                'tipo_documento' => '1',
                'numero' => '00000000',
                'nombre' => 'CLIENTES - VARIOS',
                'direccion' => 'Av. Principal',
                'email' => '',
            ];
        }

        $customersResponse = $this->api->getCustomers();
        $customers = $this->normalizeCustomersFromApi($customersResponse);
        
        foreach ($customers as $customer) {
            if ($customer['id'] == $customerId) {
                $numero = $customer['number'] ?? '00000000';
                $tipoDoc = strlen($numero) == 11 ? '6' : '1';
                
                return [
                    'tipo_documento' => $tipoDoc,
                    'numero' => $numero,
                    'nombre' => $customer['name'] ?? 'CLIENTE',
                    'direccion' => 'Av. Principal',
                    'email' => '',
                ];
            }
        }

        return [
            'tipo_documento' => '1',
            'numero' => '00000000',
            'nombre' => 'CLIENTES - VARIOS',
            'direccion' => 'Av. Principal',
            'email' => '',
        ];
    }

    private function isFuelProduct(string $name): bool
    {
        $fuelKeywords = ['gasolina', 'diesel', 'glp', 'combustible', 'kerosene', 'petróleo'];
        $lowerName = mb_strtolower($name);
        foreach ($fuelKeywords as $keyword) {
            if (str_contains($lowerName, $keyword)) {
                return true;
            }
        }
        return false;
    }

    private function getSampleProducts(): array
    {
        return [
            ['id' => 1, 'name' => 'Lápiz Faber Castell', 'code' => 'P001', 'price' => 2.50, 'category' => 'Escritorio', 'image' => 'https://wongfood.vtexassets.com/arquivos/ids/719258/L-pices-de-Color-Faber-Castell-SuperSoft-14un-1-188024285.jpg?v=638586706789030000'],
            ['id' => 2, 'name' => 'Cuaderno Norma 100 hojas', 'code' => 'P002', 'price' => 8.90, 'category' => 'Escritorio', 'image' => 'https://images.unsplash.com/photo-1585338107529-1b0415e50e3c?w=300&h=300&fit=crop'],
            ['id' => 3, 'name' => 'Goma de borrar', 'code' => 'P003', 'price' => 1.50, 'category' => 'Escritorio', 'image' => 'https://e.staedtlercdn.com/images/b_DqjaPACG9o24m5Bs65eXaXQytFD_tFDNBPO-cswYc/w:410/h:410/fn:Y3NtXzUyNjUwX1dFQl9TSU5HTEUtUFJPRFVDVF9FSU5aRUxQUk9EVUtUXzU0OTVmY2I2ZmY:t/cb:4cf4631da46f24664d66b1f2d30677356844221e/el:1/fq:jpeg:85:avif:70:webp:80/aHR0cHM6Ly9lLnN0YWVkdGxlcmNkbi5jb20vcHJvZHVjdC1pbWFnZXMvZXBpbS81MjY1MF9XRUJfU0lOR0xFLVBST0RVQ1RfRUlOWkVMUFJPRFVLVC5qcGVnPzE3NDY1MDg3MDc'],
            ['id' => 4, 'name' => 'Agua mineral 500ml', 'code' => 'B001', 'price' => 2.00, 'category' => 'Bebidas', 'image' => 'https://assets.bo-management.cord.pe/public/images/75a42afd-937e-431e-b70f-cf1f856359dc-20327848_0.jpeg'],
            ['id' => 5, 'name' => 'Gaseosa Inca Kola 1L', 'code' => 'B002', 'price' => 4.50, 'category' => 'Bebidas', 'image' => 'https://images.unsplash.com/photo-1527668752968-14dc70e27ba7?w=300&h=300&fit=crop'],
            ['id' => 6, 'name' => 'Café instantáneo', 'code' => 'B003', 'price' => 12.00, 'category' => 'Bebidas', 'image' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=300&h=300&fit=crop'],
            ['id' => 7, 'name' => 'Perfume AXE', 'code' => 'F001', 'price' => 45.00, 'category' => 'Perfumería', 'image' => 'https://wongfood.vtexassets.com/arquivos/ids/762700/Desodorante-Body-Spray-Axe-Aqua-Citrus-150ml-3-351685985.jpg?v=638796549782370000'],
            ['id' => 8, 'name' => 'Desodorante Nivea', 'code' => 'F002', 'price' => 18.00, 'category' => 'Perfumería', 'image' => 'https://images.pexels.com/photos/5983407/pexels-photo-5983407.jpeg?auto=compress&cs=tinysrgb&w=300&h=300&fit=crop'],
            ['id' => 9, 'name' => 'Regla 30cm', 'code' => 'P004', 'price' => 3.00, 'category' => 'Escritorio', 'image' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=300&h=300&fit=crop'],
            ['id' => 10, 'name' => 'Tajador grande', 'code' => 'P005', 'price' => 4.00, 'category' => 'Escritorio', 'image' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=300&h=300&fit=crop'],
            ['id' => 11, 'name' => 'Jugo de naranja', 'code' => 'B004', 'price' => 5.50, 'category' => 'Bebidas', 'image' => 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?w=300&h=300&fit=crop'],
            ['id' => 12, 'name' => 'Colonia Carolina Herrera', 'code' => 'F003', 'price' => 120.00, 'category' => 'Perfumería', 'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRAQSVmy46ZEzDC3hxHo71HWebKWgLLXcI6kw&s'],
            ['id' => 13, 'name' => 'Gasolina 95 Octanos', 'code' => 'C001', 'price' => 15.50, 'category' => 'Combustible', 'image' => 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=300&h=300&fit=crop', 'is_fuel' => true, 'fuel_price' => 15.50],
            ['id' => 14, 'name' => 'Diesel B5 S-50', 'code' => 'C002', 'price' => 14.20, 'category' => 'Combustible', 'image' => 'https://images.unsplash.com/photo-1611273426858-450d8e3c9fce?w=300&h=300&fit=crop', 'is_fuel' => true, 'fuel_price' => 14.20],
        ];
    }

    private function getSampleCustomers(): array
    {
        return [
            ['id' => 1, 'name' => 'Clientes - Varios', 'number' => '00000000'],
            ['id' => 2, 'name' => 'Pedro Díaz', 'number' => '72346556'],
            ['id' => 3, 'name' => 'María García', 'number' => '87654321'],
            ['id' => 4, 'name' => 'Carlos López', 'number' => '76543210'],
        ];
    }

    private function ensureAuthenticated()
    {
        $token = Session::get('miempresa_token');
        if (empty($token)) {
            $token = env('MIEMPRESA_API_TOKEN', '');
            Session::put('miempresa_token', $token);
        }
        if (!empty($token)) {
            $this->miEmpresaApi->setToken($token);
        }
    }

    public function getVoucherBalance($doc)
    {
        try {
            $customer = DB::connection('mysql_tenancy')->table('persons')
                ->where('type', 'customers')
                ->where('number', $doc)
                ->select('id')
                ->first();

            if (!$customer) {
                return response()->json(['success' => true, 'balance' => 0, 'message' => 'Cliente no encontrado']);
            }

            $result = DB::connection('mysql_tenancy')->table('vouchers')
                ->where('customer_id', $customer->id)
                ->where('active', 1)
                ->select(DB::raw('COALESCE(SUM(total_balance), 0) as balance'))
                ->first();

            $balance = floatval($result->balance ?? 0);

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'message' => $balance > 0 ? 'Saldo disponible' : 'Sin saldo'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching voucher balance from DB: ' . $e->getMessage());
            return response()->json(['success' => false, 'balance' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function discountVoucher(Request $request)
    {
        $data = $request->all();
        $response = $this->miEmpresaApi->discountVoucher($data['voucher_id'] ?? 0, $data['amount'] ?? 0);
        return response()->json($response);
    }

    private function getOpenCashId(): ?int
    {
        try {
            $response = $this->api->checkCashStatus();
            $data = $response['data'] ?? [];
            if (is_array($data)) {
                foreach ($data as $record) {
                    $state = $record['state'] ?? false;
                    if ($state === true || $state === '1' || $state === 1) {
                        $cashId = (int) $record['id'];
                        Log::info('Open cash found', ['id' => $cashId, 'state' => $state]);
                        return $cashId;
                    }
                }
            }
            Log::warning('No open cash record found');
        } catch (\Exception $e) {
            Log::error('Error fetching open cash ID', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function getCurrentCashRecord(): ?array
    {
        try {
            $response = $this->api->checkCashStatus();
            $data = $response['data'] ?? [];
            if (is_array($data)) {
                foreach ($data as $record) {
                    $state = $record['state'] ?? false;
                    if ($state === true || $state === '1' || $state === 1) {
                        Log::info('Current cash record found', $record);
                        return $record;
                    }
                }
            }
            Log::warning('No open cash record found for getCurrentCashRecord');
        } catch (\Exception $e) {
            Log::error('Error fetching current cash record', ['error' => $e->getMessage()]);
        }
        return null;
    }

    private function syncCashIncome(int $cashId, float $saleTotal): void
    {
        try {
            $cashRecord = $this->getCurrentCashRecord();
            if (!$cashRecord) {
                Log::warning('Cannot sync cash income: no open cash record');
                return;
            }

            $currentIncome = floatval($cashRecord['income'] ?? 0);
            $beginningBalance = floatval($cashRecord['beginning_balance'] ?? 0);
            $newIncome = $currentIncome + $saleTotal;
            $newFinalBalance = $beginningBalance + $newIncome;

            $updateResponse = $this->api->updateCashIncome($cashId, $newIncome, $newFinalBalance, $cashRecord);
            Log::info('Cash income synced', [
                'cash_id' => $cashId,
                'previous_income' => $currentIncome,
                'sale_total' => $saleTotal,
                'new_income' => $newIncome,
                'new_final_balance' => $newFinalBalance,
                'response_success' => $updateResponse['success'] ?? false,
                'response' => $updateResponse,
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing cash income', [
                'cash_id' => $cashId,
                'sale_total' => $saleTotal,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createValeSale($data)
    {
        if (!Session::get('cash_opened')) {
            return response()->json([
                'success' => false,
                'message' => 'La caja está cerrada. Debe abrir caja antes de realizar ventas.',
            ], 400);
        }

        try {
            $items = $data['items'] ?? [];
            $total = floatval($data['total'] ?? 0);

            if ($total == 0) {
                foreach ($items as $item) {
                    $total += floatval($item['price'] ?? 0) * floatval($item['quantity'] ?? 1);
                }
            }

            $customerId = intval($data['customer_id'] ?? 4);

            $payload = [
                'customer_id' => $customerId,
                'total' => $total,
                'plate_number' => $data['plate_number'] ?? null,
                'date_of_issue' => date('Y-m-d'),
                'time_of_issue' => date('H:i:s'),
            ];

            Log::info('Creating voucher via API', ['payload' => $payload]);
            $response = $this->miEmpresaApi->createVoucher($payload);

            if (isset($response['success']) && $response['success']) {
                Log::info('Voucher created via API', ['response' => $response]);

                $cashId = $this->getOpenCashId();
                Log::info('createValeSale - cash ID resolved', ['cash_id' => $cashId, 'total' => $total]);

                if ($cashId) {
                    try {
                        $this->api->associateDocumentToCash([
                            'cash_id' => $cashId,
                            'external_id' => $response['data']['external_id'] ?? $response['data']['id'] ?? null,
                            'total' => $total,
                            'document_type' => 'vale',
                        ]);
                        $this->syncCashIncome($cashId, $total);
                    } catch (\Exception $e) {
                        Log::error('Error syncing cash income after vale', ['error' => $e->getMessage()]);
                    }
                } else {
                    Log::warning('No cash ID found for vale, skipping cash sync', ['total' => $total]);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Vale creado exitosamente',
                    'data' => $response['data'] ?? []
                ]);
            }

            Log::error('API voucher creation failed', ['response' => $response]);
            return response()->json([
                'success' => false,
                'message' => $response['error'] ?? 'Error al crear vale via API',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating vale sale', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getDocumentByExternalId(Request $request, string $externalId)
    {
        Log::info('Fetching document with external_id: ' . $externalId);
        
        $response = $this->api->getDocumentByExternalId($externalId);
        
        Log::info('Document response for ' . $externalId, $response);
        
        if ($response['success'] ?? false) {
            $doc = $response['data'] ?? [];
            
            $items = [];
            if (isset($doc['items']) && is_array($doc['items'])) {
                foreach ($doc['items'] as $item) {
                    $items[] = [
                        'description' => $item['descripcion'] ?? $item['item']['description'] ?? '-',
                        'quantity' => floatval($item['cantidad'] ?? 1),
                        'unit_type_id' => $item['codigo_unidad_medida'] ?? 'NIU',
                        'unit_price' => floatval($item['precio_unitario'] ?? 0),
                        'total' => floatval($item['valor_de_venta'] ?? 0),
                    ];
                }
            }
            
            $totalVenta = 0;
            if (isset($doc['total'])) {
                $totalVenta = floatval($doc['total']);
            } elseif (isset($doc['total_venta'])) {
                $totalVenta = floatval($doc['total_venta']);
            }
            
            $customerName = 'Clientes - Varios';
            $customerNumber = '00000000';
            if (isset($doc['datos_del_cliente_o_receptor'])) {
                $customerName = $doc['datos_del_cliente_o_receptor']['apellidos_y_nombres_o_razon_social'] ?? $customerName;
                $customerNumber = $doc['datos_del_cliente_o_receptor']['numero_documento'] ?? $customerNumber;
            }
            
            $documentData = [
                'external_id' => $externalId,
                'series' => $doc['serie_documento'] ?? $doc['serie'] ?? 'NV01',
                'number' => $doc['numero_documento'] ?? '1',
                'document_type' => $doc['codigo_tipo_documento'] ?? '80',
                'date_of_issue' => $doc['fecha_de_emision'] ?? date('Y-m-d'),
                'time_of_issue' => $doc['hora_de_emision'] ?? date('H:i:s'),
                'company' => [
                    'name' => 'Factready Lite',
                    'number' => '44444444444',
                    'address' => 'CALLE DE PRUEBA , AREQUIPA ,',
                    'country' => 'AREQUIPA - AREQUIPA',
                    'email' => 'factreadylite@gmail.com',
                    'telephone' => '9999999999',
                ],
                'customer' => [
                    'name' => $customerName,
                    'number' => $customerNumber,
                    'address' => $doc['datos_del_cliente_o_receptor']['direccion'] ?? '',
                ],
                'items' => $items,
                'totals' => [
                    'total' => $totalVenta,
                    'total_igv' => floatval($doc['total_igv'] ?? 0),
                    'total_value' => floatval($doc['total_valor'] ?? $doc['total_operaciones_gravadas'] ?? 0),
                ],
                'paid' => floatval($doc['paid'] ?? $totalVenta),
                'change' => 0,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $documentData,
            ]);
        }
        
        Log::error('Document not found', ['external_id' => $externalId, 'response' => $response]);
        
        return response()->json([
            'success' => false,
            'message' => 'Documento no encontrado',
            'debug' => $response,
        ], 404);
    }
    
    public function lookupDocument(Request $request)
    {
        $type = $request->input('type');
        $number = $request->input('number');

        if (!in_array($type, ['dni', 'ruc'])) {
            return response()->json(['success' => false, 'message' => 'Tipo de documento inválido']);
        }

        if (($type === 'dni' && strlen($number) !== 8) || ($type === 'ruc' && strlen($number) !== 11)) {
            return response()->json(['success' => false, 'message' => 'Número de dígitos incorrecto']);
        }

        $token = config('vendeya.apiperu_token');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'API token no configurado']);
        }

        try {
            $response = Http::withToken($token)
                ->post("https://apiperu.dev/api/{$type}", [$type => $number]);

            $data = $response->json();

            if (($data['success'] ?? false)) {
                $info = $data['data'];
                return response()->json([
                    'success' => true,
                    'name' => $type === 'dni'
                        ? ($info['nombre_completo'] ?? '')
                        : ($info['nombre_o_razon_social'] ?? ''),
                    'trade_name' => $info['nombre_comercial'] ?? $info['direccion_completa'] ?? '',
                    'address' => $info['direccion_completa'] ?? $info['direccion'] ?? '',
                    'message' => 'Datos encontrados',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $data['message'] ?? 'No se encontraron datos',
            ]);
        } catch (\Exception $e) {
            Log::error('apiperu.dev lookup error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar el documento',
            ]);
        }
    }
}
