<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = null, string $message = 'Berhasil', int $code = 200): array
    {
        return [
            'status'    => 'success',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => now()->toISOString(),
            'service'   => 'citizen-service',
        ];
    }

    public static function error(string $message = 'Terjadi kesalahan', int $code = 500, $data = null): array
    {
        return [
            'status'    => 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => now()->toISOString(),
            'service'   => 'citizen-service',
        ];
    }
}