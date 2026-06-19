<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Cek apakah user ada dan memiliki role yang diizinkan oleh rute
        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak. Anda tidak memiliki hak akses untuk halaman ini."
            ], 403);
        }

        return $next($request);
    }
}