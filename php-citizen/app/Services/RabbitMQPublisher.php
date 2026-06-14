<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private $channel = null;

    /**
     * Inisialisasi koneksi hanya saat benar-benar dibutuhkan (Lazy Connection)
     */
    private function connect(): void
    {
        // Jika sudah terkoneksi, lewati
        if ($this->channel !== null) {
            return;
        }

        try {
            // Gunakan config(), bukan env()
            $this->connection = new AMQPStreamConnection(
                config('services.rabbitmq.host', 'rabbitmq'),
                config('services.rabbitmq.port', 5672),
                config('services.rabbitmq.user', 'guest'),
                config('services.rabbitmq.password', 'guest'),
                config('services.rabbitmq.vhost', '/')
            );

            $this->channel = $this->connection->channel();

            $this->channel->exchange_declare(
                'city.events',
                'topic',
                false,
                true,
                false
            );
        } catch (Exception $e) {
            Log::error('[RabbitMQ] Gagal konek: ' . $e->getMessage());
            $this->channel = null;
        }
    }

    public function publish(string $routingKey, array $data): void
    {
        // Panggil koneksi di sini
        $this->connect();

        if (!$this->channel) {
            Log::warning('[RabbitMQ] Channel tidak tersedia, skip publish: ' . $routingKey);
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

            Log::info('[RabbitMQ] Published: ' . $routingKey);
        } catch (Exception $e) {
            Log::error('[RabbitMQ] Gagal publish: ' . $e->getMessage());
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
