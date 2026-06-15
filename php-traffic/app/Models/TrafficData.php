<?php

namespace App\Models;

use Config\Database;
use PDO;

class TrafficData
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Simpan satu pembacaan sensor baru.
     */
    public function create(array $data): array
    {
        $sql = "INSERT INTO traffic_readings
                    (zone_id, vehicle_density, avg_speed_kmh, incident_flag, sensor_source, recorded_at)
                VALUES
                    (:zone_id, :vehicle_density, :avg_speed_kmh, :incident_flag, :sensor_source, :recorded_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':zone_id'          => $data['zone_id'],
            ':vehicle_density'  => $data['vehicle_density'],
            ':avg_speed_kmh'    => $data['avg_speed_kmh'],
            ':incident_flag'    => $data['incident_flag'] ?? 0,
            ':sensor_source'    => $data['sensor_source'] ?? null,
            ':recorded_at'      => $data['recorded_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }

    /**
     * Ambil satu record berdasarkan ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.*, z.name as zone_name
             FROM traffic_readings r
             LEFT JOIN shared_zones z ON z.id = r.zone_id
             WHERE r.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Ambil kondisi lalu lintas terkini per zona
     * (satu record terbaru per zone_id).
     */
    public function getCurrentByZone(): array
    {
        $sql = "SELECT r.*, z.name as zone_name
                FROM traffic_readings r
                INNER JOIN (
                    SELECT zone_id, MAX(recorded_at) as latest
                    FROM traffic_readings
                    GROUP BY zone_id
                ) latest ON r.zone_id = latest.zone_id AND r.recorded_at = latest.latest
                LEFT JOIN shared_zones z ON z.id = r.zone_id
                ORDER BY r.zone_id";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Riwayat data lalu lintas dengan filter opsional.
     */
    public function getHistory(array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['zone_id'])) {
            $where[]              = 'r.zone_id = :zone_id';
            $params[':zone_id']   = $filters['zone_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[]               = 'r.recorded_at >= :date_from';
            $params[':date_from']  = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]             = 'r.recorded_at <= :date_to';
            $params[':date_to']  = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $limit       = min((int) ($filters['limit'] ?? 50), 200);
        $offset      = (int) ($filters['offset'] ?? 0);

        $sql = "SELECT r.*, z.name as zone_name
                FROM traffic_readings r
                LEFT JOIN shared_zones z ON z.id = r.zone_id
                {$whereClause}
                ORDER BY r.recorded_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Hitung total record untuk pagination.
     */
    public function countHistory(array $filters = []): int
    {
        $where  = [];
        $params = [];

        if (!empty($filters['zone_id'])) {
            $where[]            = 'zone_id = :zone_id';
            $params[':zone_id'] = $filters['zone_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[]              = 'recorded_at >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[]            = 'recorded_at <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM traffic_readings {$whereClause}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}