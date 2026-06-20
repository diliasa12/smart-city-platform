<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class GatewayRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. User tidak terautentikasi.'
            ], 401);
        }

        $userRole = $user->role ?? null;

        if (!$userRole || !in_array($userRole, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Akses ditolak. Role "%s" tidak memiliki izin untuk resource ini.',
                    $userRole ?? 'unknown'
                )
            ], 403);
        }

        return $next($request);
    }
}