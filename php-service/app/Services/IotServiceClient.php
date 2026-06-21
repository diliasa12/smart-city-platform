<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IotServiceClient
{
    protected string $baseUrl;
    protected int $timeout;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = env('IOT_SERVICE_URL', 'http://localhost:3000');
        $this->timeout = (int) env('IOT_SERVICE_TIMEOUT', 5);
        $this->token = $this->getServiceToken();
    }

    protected function getServiceToken(): string
    {
        $response = Http::timeout($this->timeout)
            ->asForm()
            ->post(env('OAUTH_URL', 'http://oauth-server:3002') . '/token', [
                'grant_type'    => 'client_credentials',
                'client_id'     => env('OAUTH_CLIENT_ID', 'nodejs_iot_gateway'),
                'client_secret' => env('OAUTH_CLIENT_SECRET', 'rahasia_gateway'),
            ]);

        return $response->json('data.access_token', '');
    }

    public function getDevices(): array
    {
        $response = Http::timeout($this->timeout)
            ->withToken($this->token)
            ->retry(2, 200)
            ->get("{$this->baseUrl}/api/devices");

        if (!$response->successful()) {
            Log::warning('[IotServiceClient] Gagal mengambil daftar device', [
                'status' => $response->status(),
            ]);
            return [];
        }

        return $response->json('devices', []);
    }

    public function getDeviceData(string $deviceId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->token)
                ->retry(2, 200)
                ->get("{$this->baseUrl}/api/devices/{$deviceId}/data");

            if ($response->status() === 404) return null;
            if (!$response->successful()) return null;

            return $response->json();
        } catch (\Throwable $e) {
            Log::error("[IotServiceClient] Exception device {$deviceId}: {$e->getMessage()}");
            return null;
        }
    }
}