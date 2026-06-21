<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

/**
 * RabbitMQPublisher
 *
 * Bertugas publish payload telemetry ke queue `telemetry_ml_queue`
 * agar dikonsumsi oleh ML Service (Python).
 *
 * Payload yang dipublish menyertakan `callback_url` agar ML Service tahu
 * ke mana harus POST balik hasil prediksinya.
 */
class RabbitMQPublisher
{
    protected AMQPStreamConnection $connection;
    protected $channel;
    protected string $queueName;

    public function __construct()
    {
        $this->queueName = env('RABBITMQ_TELEMETRY_QUEUE', 'telemetry_ml_queue');

        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            (int) env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'guest'),
            env('RABBITMQ_PASSWORD', 'guest'),
        );

        $this->channel = $this->connection->channel();

        // durable=true supaya queue tetap ada walau RabbitMQ restart
        $this->channel->queue_declare($this->queueName, false, true, false, false);
    }

    /**
     * Publish satu payload telemetry ke queue.
     *
     * @param array $payload Harus minimal berisi log_id, room_id, dan data sensor mentah
     * @return bool true jika berhasil dipublish
     */
    public function publishTelemetry(array $payload): bool
    {
        try {
            $payload['callback_url'] = rtrim(env('APP_URL', 'http://php-service:8000'), '/') . '/api/telemetry/callback';

            $message = new AMQPMessage(
                json_encode($payload, JSON_UNESCAPED_SLASHES),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                ]
            );

            $this->channel->basic_publish($message, '', $this->queueName);

            return true;
        } catch (AMQPProtocolChannelException $e) {
            Log::error("[RabbitMQPublisher] Gagal publish log_id={$payload['log_id']}: {$e->getMessage()}");
            return false;
        } catch (\Throwable $e) {
            Log::error("[RabbitMQPublisher] Exception saat publish: {$e->getMessage()}");
            return false;
        }
    }

    public function close(): void
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Throwable $e) {
            Log::warning("[RabbitMQPublisher] Gagal menutup koneksi: {$e->getMessage()}");
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}