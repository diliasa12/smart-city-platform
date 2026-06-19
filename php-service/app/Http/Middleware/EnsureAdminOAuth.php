<?php

namespace App\Http\Middleware;

use App\Models\SharedOAuthToken;
use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil Bearer Token dari Header Request
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan. Akses ditolak.'
            ], 401); // 401 Unauthorized
        }

        // 2. Cek validitas token di tabel shared_oauth_tokens
        // Memastikan belum expired dan belum di-revoke
        $oauthToken = SharedOAuthToken::with('user')
            ->where('access_token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->whereNull('revoked_at')
            ->first();

        if (!$oauthToken || !$oauthToken->user) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah kedaluwarsa.'
            ], 401);
        }

        // 3. VALIDASI ROLE: Pastikan user pemilik token adalah 'admin'
        if ($oauthToken->user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Fitur ini hanya untuk Admin.'
            ], 403); // 403 Forbidden
        }

        // 4. Ikat data user ke request agar bisa dipakai di Controller jika butuh (Opsional)
        $request->setUserResolver(function () use ($oauthToken) {
            return $oauthToken->user;
        });

        return $next($request);
    }
}