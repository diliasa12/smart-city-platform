<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GatewayAuth
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->header('x-user-id');
        $role   = $request->header('x-user-role');

        if (!$userId) {
            return response()->json([
                'status'    => 'error',
                'code'      => 401,
                'data'      => null,
                'message'   => 'Tidak terautentikasi. Request harus melalui API Gateway.',
                'timestamp' => now()->toISOString(),
                'service'   => 'citizen-service',
            ], 401);
        }

        $request->merge([
            'auth_user_id'   => (int) $userId,
            'auth_user_role' => $role ?? 'citizen',
        ]);

        return $next($request);
    }
}