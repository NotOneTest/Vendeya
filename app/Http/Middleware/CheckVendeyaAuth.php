<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CheckVendeyaAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Session::has('vendeya_user')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
            }
            return redirect()->route('vendeya.login');
        }
        return $next($request);
    }
}
