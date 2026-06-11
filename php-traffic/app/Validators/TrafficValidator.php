<?php

namespace App\Validators;

class TrafficValidator
{
    /**
     * Validasi payload untuk traffic reading.
     * Melempar InvalidArgumentException jika ada field yang tidak valid.
     */
    public static function validateReading(array $data): array
    {
        $errors = [];

        // zone_id wajib, integer positif
        if (empty($data['zone_id']) || !is_numeric($data['zone_id']) || (int)$data['zone_id'] <= 0) {
            $errors[] = 'zone_id wajib diisi dan harus integer positif';
        }

        // vehicle_density wajib, >= 0
        if (!isset($data['vehicle_density']) || !is_numeric($data['vehicle_density']) || $data['vehicle_density'] < 0) {
            $errors[] = 'vehicle_density wajib diisi dan harus >= 0';
        }

        // avg_speed_kmh wajib, >= 0
        if (!isset($data['avg_speed_kmh']) || !is_numeric($data['avg_speed_kmh']) || $data['avg_speed_kmh'] < 0) {
            $errors[] = 'avg_speed_kmh wajib diisi dan harus >= 0';
        }

        // incident_flag opsional, hanya 0 atau 1
        if (isset($data['incident_flag']) && !in_array((int)$data['incident_flag'], [0, 1], true)) {
            $errors[] = 'incident_flag hanya boleh bernilai 0 atau 1';
        }

        if ($errors) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }

        return [
            'zone_id'          => (int)   $data['zone_id'],
            'vehicle_density'  => (int)   $data['vehicle_density'],
            'avg_speed_kmh'    => (float) $data['avg_speed_kmh'],
            'incident_flag'    => (int)   ($data['incident_flag'] ?? 0),
            'sensor_source'    => $data['sensor_source'] ?? null,
            'recorded_at'      => $data['recorded_at']   ?? date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Validasi payload untuk incident.
     */
    public static function validateIncident(array $data): array
    {
        $errors         = [];
        $validTypes     = ['accident', 'congestion', 'road_closure', 'hazard', 'other'];
        $validSeverities = ['low', 'medium', 'high', 'critical'];

        if (empty($data['zone_id']) || !is_numeric($data['zone_id']) || (int)$data['zone_id'] <= 0) {
            $errors[] = 'zone_id wajib diisi dan harus integer positif';
        }

        if (!empty($data['type']) && !in_array($data['type'], $validTypes, true)) {
            $errors[] = 'type tidak valid. Pilihan: ' . implode(', ', $validTypes);
        }

        if (!empty($data['severity']) && !in_array($data['severity'], $validSeverities, true)) {
            $errors[] = 'severity tidak valid. Pilihan: ' . implode(', ', $validSeverities);
        }

        if ($errors) {
            throw new \InvalidArgumentException(implode('; ', $errors));
        }

        return [
            'zone_id'     => (int) $data['zone_id'],
            'type'        => $data['type']        ?? 'other',
            'severity'    => $data['severity']    ?? 'low',
            'description' => $data['description'] ?? null,
            'reported_at' => $data['reported_at'] ?? date('Y-m-d H:i:s'),
        ];
    }
}