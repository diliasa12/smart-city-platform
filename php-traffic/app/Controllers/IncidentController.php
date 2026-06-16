<?php

namespace App\Controllers;

use App\Models\Incident;
use App\Services\RabbitMQPublisher;
use App\Validators\TrafficValidator;

class IncidentController
{
    private Incident          $model;
    private RabbitMQPublisher $publisher;

    public function __construct()
    {
        $this->model     = new Incident();
        $this->publisher = new RabbitMQPublisher();
    }

    /**
     * POST /api/traffic/incidents
     * Laporkan insiden baru (kecelakaan, kemacetan, penutupan jalan, dll).
     */
    public function store(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $validated = TrafficValidator::validateIncident($body);
        } catch (\InvalidArgumentException $e) {
            $this->respond(422, null, $e->getMessage(), 'error');
            return;
        }

        $record = $this->model->create($validated);

        // Publish event anomaly.alert jika severity critical/high
        if (in_array($record['severity'], ['critical', 'high'], true)) {
            $this->publisher->publish('anomaly.alert', [
                'source'    => 'traffic-service',
                'zone_id'   => $record['zone_id'],
                'zone_name' => $record['zone_name'] ?? null,
                'type'      => 'traffic_incident',
                'severity'  => $record['severity'],
                'detail'    => $record,
                'timestamp' => date('c'),
            ]);
        }

        $this->respond(201, $record, 'Insiden berhasil dilaporkan');
    }

    /**
     * GET /api/traffic/incidents
     * Daftar insiden yang belum diselesaikan.
     */
    public function active(): void
    {
        $rows = $this->model->getActive();
        $this->respond(200, $rows, 'Daftar insiden aktif');
    }

    /**
     * PATCH /api/traffic/incidents/:id/resolve
     * Tandai insiden sebagai selesai.
     */
    public function resolve(int $id): void
    {
        $record = $this->model->resolve($id);
        if (!$record) {
            $this->respond(404, null, "Insiden ID {$id} tidak ditemukan", 'error');
            return;
        }
        $this->respond(200, $record, 'Insiden berhasil diselesaikan');
    }

    // ── Helper ──────────────────────────────────────────────────────────────

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