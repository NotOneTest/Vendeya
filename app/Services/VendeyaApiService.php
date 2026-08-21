<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VendeyaApiService
{
    private ?string $baseUrl;
    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = config('vendeya.base_url', 'https://factreadylite.fe-estudioscreativos.com');
        $this->token = config('vendeya.token', 'nDCTztmGGbb0JbJkWDSH8ikgR99zOfTCVVDCMxPXNsmufINNav');
    }

    private function getBasePath(): string
    {
        return config('vendeya.base_path', '');
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-CSRF-TOKEN' => 'test',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    public function get(string $endpoint, array $params = []): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(config('vendeya.timeout', 30))
                ->withoutVerifying()
                ->get($this->baseUrl . $endpoint, $params);

            return $response->successful() ? $response->json() : ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function post(string $endpoint, array $data = []): array
    {
        try {
            $fullUrl = $this->baseUrl . $this->getBasePath() . $endpoint;
            $response = Http::withHeaders($this->headers())
                ->timeout(config('vendeya.timeout', 30))
                ->withoutVerifying()
                ->post($fullUrl, $data);

            Log::info('API POST Response', [
                'endpoint' => $endpoint,
                'url' => $response->effectiveUri(),
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $responseData = $response->successful() ? $response->json() : ['success' => false, 'error' => $response->body(), 'status' => $response->status()];
            
            return $responseData;
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCustomers(): array
    {
        return $this->get('/persons/customers/records', [
            'column' => 'name',
            'isEcommerce' => 'false',
            'isPharmacy' => 'false',
            'isRestaurant' => 'false',
            'list_value' => 'all',
            'page' => 1,
            'show_disabled' => 'all',
            'sort_direction' => 'desc',
            'sort_field' => 'id',
            'value' => ''
        ]);
    }

    public function createCustomer(array $data): array
    {
        $payload = array_merge([
            'type' => 'customers',
            'identity_document_type_id' => '1',
            'country_id' => 'PE',
            'perception_agent' => false,
            'percentage_perception' => 0,
            'more_address' => [],
            'location_id' => [],
        ], $data);

        return $this->post('/api/person', $payload);
    }

    public function getProducts(): array
    {
        $allProducts = [];
        $page = 1;
        $hasMore = true;
        
        while ($hasMore) {
            $response = $this->get('/items/records', [
                'column' => 'description',
                'isEcommerce' => 'false',
                'isPharmacy' => 'false',
                'isRestaurant' => 'false',
                'list_value' => 'all',
                'page' => $page,
                'show_disabled' => 'all',
                'sort_direction' => 'desc',
                'sort_field' => 'id',
                'type' => 'PRODUCTS',
                'value' => ''
            ]);
            
            if (isset($response['data']) && !empty($response['data'])) {
                $allProducts = array_merge($allProducts, $response['data']);
                $page++;
            } else {
                $hasMore = false;
            }
        }
        
        return ['data' => $allProducts];
    }

    public function getCategories(): array
    {
        return $this->get('/items/records', [
            'column' => 'description',
            'list_value' => 'all',
            'page' => 1,
            'show_disabled' => 'all',
            'sort_direction' => 'desc',
            'sort_field' => 'id',
            'type' => 'CATEGORIES',
            'value' => ''
        ]);
    }

    public function createProduct(array $data): array
    {
        return $this->post('/api/item', $data);
    }

    public function createSale(array $data): array
    {
        return $this->post('/api/sale/documents', $data);
    }

    public function createDocument(array $data): array
    {
        return $this->post('/api/documents', $data);
    }

    public function createDocumentStore(array $data): array
    {
        return $this->post('/sale-note', $data);
    }

    public function createInvoiceDocument(array $data): array
    {
        return $this->post('/documents', $data);
    }

    public function getSeries(): array
    {
        return $this->get('/series/records', [
            'column' => 'id',
            'value' => ''
        ]);
    }

    public function getEstablishments(): array
    {
        return $this->get('/establishments/records', [
            'column' => 'id',
            'value' => ''
        ]);
    }

    public function getDocumentTypes(): array
    {
        return $this->get('/cat/document_types/records');
    }

    public function checkCashStatus(): array
    {
        // Obtener registros de caja y buscar si hay alguna abierta
        $response = $this->get('/cash/records', [
            'column' => 'id',
            'isEcommerce' => 'false',
            'isPharmacy' => 'false',
            'isRestaurant' => 'false',
            'list_value' => 'open',
            'page' => 1,
            'show_disabled' => 'all',
            'sort_direction' => 'desc',
            'sort_field' => 'id',
            'value' => ''
        ]);
        
        return $response;
    }

    public function searchCash(array $params = []): array
    {
        return $this->get('/cash/search', $params);
    }

    public function getCashGeneralReport(array $params = []): array
    {
        return $this->get('/cash/general-report', $params);
    }

    public function getCashExpenseIncomeReport(array $params = []): array
    {
        return $this->get('/cash/expense-income-report', $params);
    }

    public function getCashIncomeReport(array $params = []): array
    {
        return $this->get('/cash/income-report', $params);
    }

    public function associateDocumentToCash(array $data): array
    {
        return $this->post('/cash/associate-document', $data);
    }

    public function updateCashIncome(int $cashId, float $income, float $finalBalance, array $fullRecord): array
    {
        return $this->post('/cash', [
            'id' => $cashId,
            'user_id' => $fullRecord['user_id'] ?? 1,
            'date_opening' => $fullRecord['date_opening'] ?? null,
            'time_opening' => $fullRecord['time_opening'] ?? null,
            'date_closed' => $fullRecord['date_closed'] ?? null,
            'time_closed' => $fullRecord['time_closed'] ?? null,
            'beginning_balance' => (string) ($fullRecord['beginning_balance'] ?? 0),
            'final_balance' => $finalBalance,
            'income' => $income,
            'state' => true,
            'reference_number' => $fullRecord['reference_number'] ?? '',
        ]);
    }

    public function openCash(float $initialAmount, int $userId = 1): array
    {
        return $this->post('/cash', [
            'id' => null,
            'user_id' => $userId,
            'date_opening' => null,
            'time_opening' => null,
            'date_closed' => null,
            'time_closed' => null,
            'beginning_balance' => (string) $initialAmount,
            'final_balance' => 0,
            'income' => 0,
            'state' => true,
            'reference_number' => '',
        ]);
    }

    public function closeCash(float $finalAmount): array
    {
        return $this->get('/cash/closing');
    }

    public function closeCashById(int $cashId): array
    {
        return $this->get('/cash/close/' . $cashId);
    }

    public function getCashRecords(array $params = []): array
    {
        $defaultParams = [
            'column' => 'income',
            'isEcommerce' => 'false',
            'isPharmacy' => 'false',
            'isRestaurant' => 'false',
            'list_value' => 'all',
            'page' => 1,
            'show_disabled' => 'all',
            'sort_direction' => 'desc',
            'sort_field' => 'id',
            'value' => ''
        ];
        $params = array_merge($defaultParams, $params);
        return $this->get('/cash/records', $params);
    }

    public function getUsers(): array
    {
        return $this->get('/users/records');
    }

    public function login(string $email, string $password): array
    {
        return $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function createPerception(array $data): array
    {
        return $this->post('/api/perceptions', $data);
    }

    public function createRetention(array $data): array
    {
        return $this->post('/api/retentions', $data);
    }

    public function getDocumentByExternalId(string $externalId): array
    {
        $response = $this->get('/sale-notes/records', [
            'external_id' => $externalId,
            'column' => 'external_id',
            'value' => $externalId,
            'page' => 1,
            'list_value' => 'all'
        ]);
        
        if (isset($response['success']) && $response['success'] && isset($response['data']) && !empty($response['data'])) {
            return [
                'success' => true,
                'data' => $response['data'][0],
            ];
        }
        
        $response = $this->get('/documents/records', [
            'external_id' => $externalId,
            'column' => 'external_id',
            'value' => $externalId,
            'page' => 1,
            'list_value' => 'all'
        ]);
        
        if (isset($response['success']) && $response['success'] && isset($response['data']) && !empty($response['data'])) {
            return [
                'success' => true,
                'data' => $response['data'][0],
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Documento no encontrado',
            'response' => $response,
        ];
    }

    public function printDocument(string $externalId, string $format = 'ticket'): array
    {
        return $this->get('/print/document/' . $externalId . '/' . $format);
    }
}
