<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        $dbStatus = 'healthy';

        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            $dbStatus = 'unhealthy';
        }

        $code = $dbStatus === 'healthy' ? 200 : 503;

        return response()->json(
            ApiResponse::success([
                'service'        => 'citizen-service',
                'database'       => $dbStatus,
                'uptime_seconds' => (int) (microtime(true) - LARAVEL_START),
            ], $dbStatus === 'healthy' ? 'Service sehat' : 'Database tidak bisa dijangkau'),
            $code
        );
    }
}