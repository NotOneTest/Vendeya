<?php

namespace App\Http\Controllers\Vendeya;

use App\Http\Controllers\Controller;
use App\Services\VendeyaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    protected VendeyaApiService $api;

    public function __construct(VendeyaApiService $api)
    {
        $this->api = $api;
    }

    public function showLoginForm()
    {
        if (Session::has('vendeya_user')) {
            return redirect()->route('vendeya.pos');
        }

        $fullApiUrl = config('vendeya.base_url', 'https://factreadylite.fe-estudioscreativos.com');
        $baseApiUrl = preg_replace('#/api$#', '', $fullApiUrl);
        $logo = $baseApiUrl . '/storage/uploads/logos/logo_44444444444.png';

        return view('vendeya.auth.login', compact('logo'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $response = $this->api->login($credentials['email'], $credentials['password']);

        if (isset($response['success']) && $response['success']) {
            $userData = $response['data'] ?? [];
            
            $usersResponse = $this->api->getUsers();
            $userName = 'Usuario';
            if (isset($usersResponse['success']) && $usersResponse['success']) {
                $users = $usersResponse['data'] ?? [];
                foreach ($users as $user) {
                    if (($user['email'] ?? '') === $credentials['email']) {
                        $userName = $user['name'] ?? $user['email'];
                        break;
                    }
                }
            }
            
            Session::put('vendeya_user', [
                'id' => $userData['id'] ?? 1,
                'name' => $userName,
                'email' => $credentials['email'],
                'company' => $userData['company'] ?? 'FactReady Lite',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'redirect' => route('vendeya.pos'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Credenciales incorrectas',
            'debug' => $response,
        ], 401);
    }

    public function logout()
    {
        Session::forget('vendeya_user');
        Session::forget('cash_opened');
        return redirect()->route('vendeya.login');
    }
}
