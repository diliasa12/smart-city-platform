<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST']     ?? 'mysql';
            $port = $_ENV['DB_PORT']     ?? '3306';
            $name = $_ENV['DB_NAME']     ?? 'smartcity';
            $user = $_ENV['DB_USER']     ?? 'svc_traffic';
            $pass = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(503);
                echo json_encode([
                    'status'    => 'error',
                    'code'      => 503,
                    'data'      => null,
                    'message'   => 'Database tidak dapat dijangkau',
                    'timestamp' => date('c'),
                    'service'   => 'traffic-service',
                ]);
                exit;
            }
        }

        return self::$instance;
    }
}