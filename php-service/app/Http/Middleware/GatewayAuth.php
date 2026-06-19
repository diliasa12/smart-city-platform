<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class GatewayAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil header yang sudah disuntikkan oleh API Gateway
        $userId   = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');

        // Jika gateway lupa mengirimkan header penanda, tolak request
        if (!$userRole) {
            return response()->json([
                'success' => false, 
                'message' => 'Akses ilegal. Request harus melalui API Gateway.'
            ], 401);
        }

        // 2. KONDISI: Jika token berupa Machine-to-Machine / Service-to-Service (Tanpa User)
        if ($userRole === 'service' || empty($userId)) {
            $serviceUser = (object) [
                'id' => null,
                'role' => 'service',
                'name' => 'Internal Service'
            ];
            $request->setUserResolver(fn () => $serviceUser);
            return $next($request);
        }

        // 3. KONDISI: Jika token berupa User / Admin
        // Kita cukup mencari user di DB lokal berdasarkan ID yang dikirim Gateway
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User terautentikasi tidak ditemukan di sistem internal.'
            ], 401);
        }

        // Set user login ke context Laravel agar bisa dipanggil lewat $request->user()
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}