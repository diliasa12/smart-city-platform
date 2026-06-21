<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private $channel = null;

    /**
     * Inisialisasi koneksi hanya saat benar-benar dibutuhkan (Lazy Connection)
     */
    private function connect(): void
    {
        if ($this->channel !== null) {
            return;
        }

        try {
            $this->connection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'localhost'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest'),
                env('RABBITMQ_VHOST', '/')
            );

            $this->channel = $this->connection->channel();

            // Exchange sesuai konvensi yang sudah dipakai di proyek: city.events
            $this->channel->exchange_declare(
                'city.events',
                'topic',
                false,
                true,
                false
            );
        } catch (Exception $e) {
            \Log::error('[RabbitMQ] Gagal konek: ' . $e->getMessage());
            $this->channel = null;
        }
    }

    /**
     * Publish event ke RabbitMQ.
     *
     * @param string $routingKey Contoh: 'telemetry.new'
     * @param array $data Payload yang dikirim
     */
    public function publish(string $routingKey, array $data): void
    {
        $this->connect();

        if (!$this->channel) {
            \Log::warning('[RabbitMQ] Channel tidak tersedia, skip publish: ' . $routingKey);
            return;
        }

        try {
            $payload = json_encode($data);
            $message = new AMQPMessage($payload, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $this->channel->basic_publish(
                $message,
                'city.events',
                $routingKey
            );

            \Log::info('[RabbitMQ] Published: ' . $routingKey);
        } catch (Exception $e) {
            \Log::error('[RabbitMQ] Gagal publish: ' . $e->getMessage());
        }
    }

    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            // abaikan
        }
    }
}