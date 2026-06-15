<?php

namespace Config;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQ
{
    private static ?AMQPStreamConnection $connection = null;

    public static function getConnection(): ?AMQPStreamConnection
    {
        if (self::$connection === null) {
            try {
                self::$connection = new AMQPStreamConnection(
                    $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
                    (int) ($_ENV['RABBITMQ_PORT'] ?? 5672),
                    $_ENV['RABBITMQ_USER'] ?? 'guest',
                    $_ENV['RABBITMQ_PASS'] ?? 'guest'
                );
            } catch (\Exception $e) {
                // RabbitMQ tidak wajib untuk service tetap jalan
                error_log('[RabbitMQ] Koneksi gagal: ' . $e->getMessage());
                return null;
            }
        }

        return self::$connection;
    }
}