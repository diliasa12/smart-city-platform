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
        $userId   = $request->header('X-User-Id');
        $userRole = $request->header('X-User-Role');

        if (!$userRole) {
            return response()->json([
                'success' => false, 
                'message' => 'Akses ilegal. Request harus melalui API Gateway.'
            ], 401);
        }

        if ($userRole === 'service' || empty($userId)) {
            $serviceUser = (object) [
                'id' => null,
                'role' => 'service',
                'name' => 'Internal Service'
            ];
            $request->setUserResolver(fn () => $serviceUser);
            return $next($request);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false, 
                'message' => 'User terautentikasi tidak ditemukan di sistem internal.'
            ], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}