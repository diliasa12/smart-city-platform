<?php

namespace App\Services;

use Config\RabbitMQ;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQPublisher
{
    private const EXCHANGE = 'city.events';

    /**
     * Publish satu event ke exchange city.events.
     *
     * @param string $routingKey  Contoh: 'traffic.new'
     * @param array  $payload     Data yang akan dikirim (akan di-encode ke JSON)
     */
    public function publish(string $routingKey, array $payload): bool
    {
        try {
            $connection = RabbitMQ::getConnection();
            if ($connection === null) {
                error_log("[RabbitMQ] Tidak dapat publish '{$routingKey}': koneksi null");
                return false;
            }

            $channel = $connection->channel();

            // Deklarasi exchange (topic type, durable)
            $channel->exchange_declare(
                self::EXCHANGE,
                'topic',
                false,  // passive
                true,   // durable
                false   // auto_delete
            );

            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

            $msg = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'timestamp'     => time(),
                'app_id'        => 'traffic-service',
            ]);

            $channel->basic_publish($msg, self::EXCHANGE, $routingKey);
            $channel->close();

            error_log("[RabbitMQ] Published '{$routingKey}': {$body}");
            return true;

        } catch (\Exception $e) {
            // Jangan crash service jika RabbitMQ tidak tersedia
            error_log("[RabbitMQ] Gagal publish '{$routingKey}': " . $e->getMessage());
            return false;
        }
    }
}