<?php

namespace App\Models;

use Config\Database;
use PDO;

class Incident
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $data): array
    {
        $sql = "INSERT INTO traffic_incidents
                    (zone_id, type, severity, description, reported_at)
                VALUES
                    (:zone_id, :type, :severity, :description, :reported_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':zone_id'     => $data['zone_id'],
            ':type'        => $data['type']        ?? 'other',
            ':severity'    => $data['severity']    ?? 'low',
            ':description' => $data['description'] ?? null,
            ':reported_at' => $data['reported_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, z.name as zone_name
             FROM traffic_incidents i
             LEFT JOIN shared_zones z ON z.id = i.zone_id
             WHERE i.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getActive(): array
    {
        $sql = "SELECT i.*, z.name as zone_name
                FROM traffic_incidents i
                LEFT JOIN shared_zones z ON z.id = i.zone_id
                WHERE i.resolved_at IS NULL
                ORDER BY i.reported_at DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function resolve(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "UPDATE traffic_incidents SET resolved_at = NOW() WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
        return $this->findById($id);
    }
}