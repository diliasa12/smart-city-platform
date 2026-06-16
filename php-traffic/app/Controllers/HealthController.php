<?php

namespace App\Controllers;

use Config\Database;

class HealthController
{
    /**
     * GET /health
     * Cek koneksi database. Dipakai oleh Docker HEALTHCHECK dan API Gateway.
     */
    public function index(): void
    {
        $dbStatus = 'healthy';

        try {
            Database::getConnection()->query('SELECT 1');
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        $code = $dbStatus === 'healthy' ? 200 : 503;

        http_response_code($code);
        echo json_encode([
            'status'    => $dbStatus === 'healthy' ? 'success' : 'error',
            'code'      => $code,
            'data'      => [
                'service'        => 'traffic-service',
                'database'       => $dbStatus,
                'uptime_seconds' => (int) (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']),
            ],
            'message'   => $dbStatus === 'healthy' ? 'Traffic service sehat' : 'Database tidak dapat dijangkau',
            'timestamp' => date('c'),
            'service'   => 'traffic-service',
        ], JSON_PRETTY_PRINT);
    }
}