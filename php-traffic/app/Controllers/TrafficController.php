<?php

namespace App\Controllers;

use App\Models\TrafficData;
use App\Services\RabbitMQPublisher;
use App\Validators\TrafficValidator;

class TrafficController
{
    private TrafficData       $model;
    private RabbitMQPublisher $publisher;

    public function __construct()
    {
        $this->model     = new TrafficData();
        $this->publisher = new RabbitMQPublisher();
    }

    /**
     * POST /api/traffic/readings
     * Submit satu pembacaan sensor lalu lintas baru.
     */
    public function store(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $validated = TrafficValidator::validateReading($body);
        } catch (\InvalidArgumentException $e) {
            $this->respond(422, null, $e->getMessage(), 'error');
            return;
        }

        $record = $this->model->create($validated);

        // Publish event ke RabbitMQ → dikonsumsi Python ML Service
        $this->publisher->publish('traffic.new', [
            'id'            => $record['id'],
            'zone_id'       => $record['zone_id'],
            'location'      => $record['zone_name'] ?? 'zone' . $record['zone_id'],
            'density'       => $record['vehicle_density'],
            'speed_kmh'     => $record['avg_speed_kmh'],
            'incident_flag' => $record['incident_flag'],
            'hour'          => (int) date('G', strtotime($record['recorded_at'])),
            'day_of_week'   => (int) date('N', strtotime($record['recorded_at'])) - 1,
            'timestamp'     => $record['recorded_at'],
        ]);

        $this->respond(201, $record, 'Data lalu lintas berhasil disimpan');
    }

    /**
     * GET /api/traffic/current
     * Status lalu lintas real-time per zona (satu data terbaru per zona).
     */
    public function current(): void
    {
        $rows = $this->model->getCurrentByZone();

        // Tambahkan label congestion_level per record
        $data = array_map(function ($row) {
            $row['congestion_level'] = $this->congestionLabel((int) $row['vehicle_density']);
            return $row;
        }, $rows);

        $this->respond(200, $data, 'Status lalu lintas terkini');
    }

    /**
     * GET /api/traffic/history
     * Riwayat data lalu lintas dengan filter opsional:
     *   ?zone_id=1&date_from=2025-01-01&date_to=2025-12-31&limit=50&offset=0
     */
    public function history(): void
    {
        $filters = [
            'zone_id'   => $_GET['zone_id']   ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to'   => $_GET['date_to']   ?? null,
            'limit'     => $_GET['limit']     ?? 50,
            'offset'    => $_GET['offset']    ?? 0,
        ];

        $rows  = $this->model->getHistory($filters);
        $total = $this->model->countHistory($filters);

        $this->respond(200, [
            'items'      => $rows,
            'total'      => $total,
            'limit'      => (int) $filters['limit'],
            'offset'     => (int) $filters['offset'],
        ], 'Riwayat data lalu lintas');
    }

    // ── Helper ──────────────────────────────────────────────────────────────

    private function congestionLabel(int $density): string
    {
        if ($density > 80) return 'Padat';
        if ($density > 40) return 'Sedang';
        return 'Lancar';
    }

    private function respond(int $code, mixed $data, string $message, string $status = 'success'): void
    {
        http_response_code($code);
        echo json_encode([
            'status'    => $status,
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c'),
            'service'   => 'traffic-service',
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}