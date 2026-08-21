<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class MiEmpresaApiService
{
    private $baseUrl;
    private $token;

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function __construct()
    {
        $this->baseUrl = config('miempresa.base_url', 'http://miempresa.pro-8-2026.test');
        $this->token = Session::get('miempresa_token', config('miempresa.token', ''));
    }

    private function headers(): array
    {
        $headers = ['Accept' => 'application/json'];
        if (!empty($this->token)) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        return $headers;
    }

    public function login(string $email, string $password): bool
    {
        // Skip login, use token directly
        $token = env('MIEMPRESA_API_TOKEN', 'Y8C2TYFnFBJJgodxDap2vEbSjr67vj8PKAu1eaiZqgzoxdNToA');
        if (!empty($token)) {
            Session::put('miempresa_token', $token);
            $this->token = $token;
            Log::info('MiEmpresa API token set directly');
            return true;
        }
        
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/api/login', [
                'email' => $email,
                'password' => $password,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? null;
                if ($token) {
                    Session::put('miempresa_token', $token);
                    $this->token = $token;
                    Log::info('MiEmpresa API login successful');
                    return true;
                }
            }
            Log::error('MiEmpresa API login failed', ['status' => $response->status(), 'body' => substr($response->body(), 0, 500)]);
            return false;
        } catch (\Exception $e) {
            Log::error('MiEmpresa login exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function get(string $endpoint, array $params = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $response = Http::withHeaders($this->headers())
                ->timeout(config('miempresa.timeout', 30))
                ->get($url, $params);
            if ($response->successful()) {
                return $response->json() ?? [];
            }
            Log::error('MiEmpresa API GET failed', ['status' => $response->status(), 'body' => substr($response->body(), 0, 500)]);
            return ['success' => false, 'error' => 'HTTP ' . $response->status()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function post(string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(config('miempresa.timeout', 30))
                ->post($this->baseUrl . $endpoint, $data);
            return $response->successful() ? $response->json() : ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createDocument(array $data): array
    {
        return $this->post('/api/documents', $data);
    }

    public function createSaleNote(array $data): array
    {
        return $this->post('/api/sale-note', $data);
    }

    public function createVoucher(array $data): array
    {
        return $this->post('/api/vouchers', $data);
    }

    public function createCustomer(array $data): array
    {
        return $this->post('/api/persons/customers', $data);
    }

    public function getCustomers(array $params = []): array
    {
        $result = $this->get('/api/persons/customers');
        if (isset($result['data']['customers'])) {
            return ['data' => $result['data']['customers']];
        }
        if (isset($result['data']) && is_array($result['data'])) {
            return ['data' => $result['data']];
        }
        return ['data' => []];
    }

    public function getVoucherBalance(string $customerDoc): array
    {
        $result = $this->get('/api/vouchers/balance/' . $customerDoc);
        return $result;
    }

    public function getVouchersByCustomer(string $customerDoc): array
    {
        return $this->get('/api/vouchers/customer/' . $customerDoc);
    }

    public function discountVoucher($voucherId, $amount): array
    {
        return $this->post('/api/vouchers/discount', [
            'voucher_id' => $voucherId,
            'amount' => $amount,
        ]);
    }

    public function getProducts(array $params = []): array
    {
        $params = array_merge(['input' => '', 'limit' => 100], $params);
        $result = $this->get('/api/public/document/search-items', $params);
        if (is_array($result) && isset($result['success']) && $result['success'] === true) {
            return $result['data'] ?? [];
        }
        if (is_array($result) && count($result) > 0) {
            return $result;
        }
        return [];
    }
}
