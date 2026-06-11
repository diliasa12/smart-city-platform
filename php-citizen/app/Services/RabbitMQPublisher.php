<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    private $connection;
    private $channel;

    public function __construct()
    {
        try {
            $this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASSWORD', 'guest'),
                env('RABBITMQ_VHOST', '/')
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
        if (!$this->channel) {
            Log::warning('[RabbitMQ] Channel tidak tersedia, skip publish: ' . $routingKey);
            return;
        }

        try {
            $payload = json_encode($data);
            $message = new \PhpAmqpLib\Message\AMQPMessage($payload, [
                'content_type'  => 'application/json',
                'delivery_mode' => \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT,
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