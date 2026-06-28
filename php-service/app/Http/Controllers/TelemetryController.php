<?php

namespace App\Http\Controllers;

use App\Models\EnvRoomTelemetryLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\EnvDeviceCommand;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class TelemetryController extends Controller
{
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id'      => 'required|integer|exists:env_rooms,id',
            'temperature'  => 'required|numeric',
            'humidity'     => 'required|numeric',
            'decibel_level'=> 'required|numeric',
        ]);

        $log = EnvRoomTelemetryLog::create([
            'room_id'       => $validated['room_id'],
            'temperature'   => $validated['temperature'],
            'humidity'      => $validated['humidity'],
            'decibel_level' => $validated['decibel_level'],
            'ml_status'     => 'pending',
        ]);

        // payload python ml
        $now = Carbon::now();
        $payload = json_encode([
            'log_id'        => $log->id, 
            'temperature'   => (float) $log->temperature,
            'humidity'      => (float) $log->humidity,
            'decibel_level' => (float) $log->decibel_level,
            'hour'          => $now->hour,
            'is_weekend'    => $now->isWeekend() ? 1 : 0,
            'callback_url'  => env('APP_URL', 'http://localhost:8000') . '/api/telemetry/callback'
        ]);

        // kirim data  ke antrean rabbitmq
        try {
            $host = env('RABBITMQ_HOST', 'localhost');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'guest');
            $pass = env('RABBITMQ_PASSWORD', 'guest');

            $connection = new AMQPStreamConnection($host, $port, $user, $pass);
            $channel = $connection->channel();

            // Deklarasi queue dengan parameter: (queue, passive, durable, exclusive, auto_delete)
            $channel->queue_declare('telemetry_ml_queue', false, true, false, false);

            $msg = new AMQPMessage($payload, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $channel->basic_publish($msg, '', 'telemetry_ml_queue');

            $channel->close();
            $connection->close();
            
        } catch (\Exception $e) {
            // Jika RabbitMQ mati, simpan error di log Laravel agar sistem tidak crash
            Log::error("[RabbitMQ] Gagal mengirim antrean untuk log_id {$log->id}: " . $e->getMessage());
        }

        return ApiResponse::success($log, 'Data telemetry diterima dan dikirim ke antrean ML', 201);
    }

    public function callback(Request $request)
    {
        
        $validated = $request->validate([
            'telemetry_log_id'        => 'required|integer|exists:env_room_telemetry_logs,id',
            'ml_classification_status'=> 'required|in:nyaman,cukup_nyaman,tidak_nyaman',
            'predicted_next_busy_hour'=> 'required|integer|min:0|max:23',
            'is_anomaly'              => 'required|boolean',
        ]);

        $log = EnvRoomTelemetryLog::with('room')->find($validated['telemetry_log_id']);

        
        $log->update([
            'ml_classification_status' => $validated['ml_classification_status'],
            'predicted_next_busy_hour' => $validated['predicted_next_busy_hour'],
            'is_anomaly'               => $validated['is_anomaly'],
            'ml_status'                => 'done',
        ]);

        $room = $log->room;

        if ($room && $room->is_active) {
            if ($validated['ml_classification_status'] === 'tidak_nyaman') {
                $this->dispatchDeviceCommand($room->id, $room->device_token, 'relay', 1);
                $this->dispatchDeviceCommand($room->id, $room->device_token, 'led', 1);
            } else {
                $this->dispatchDeviceCommand($room->id, $room->device_token, 'relay', 0);
                $this->dispatchDeviceCommand($room->id, $room->device_token, 'led', 0);
            }
        }

        return response()->json([
            'meta' => ['status' => 'success', 'message' => 'Hasil prediksi ML berhasil disimpan.', 'code' => 200],
            'data' => $log->fresh()
        ]);
    }

    private function dispatchDeviceCommand($roomId, $deviceToken, $commandType, $value)
    {
        $command = EnvDeviceCommand::create([
            'room_id'      => $roomId,
            'command_type' => $commandType,
            'payload'      => ['command' => $commandType, 'value' => $value],
            'status'       => 'pending'
        ]);

        try {
            $iotServiceUrl = env('IOT_SERVICE_URL', 'http://iot-service:3000');
            $response = Http::timeout(3)->post("{$iotServiceUrl}/api/devices/{$deviceToken}/command", [
                'command' => $commandType,
                'value'   => $value
            ]);

            $command->update([
                'status'      => $response->successful() ? 'sent' : 'failed',
                'executed_at' => Carbon::now()
            ]);
        } catch (\Exception $e) {
            Log::error("[TelemetryController] Command gagal: {$e->getMessage()}");
            $command->update(['status' => 'failed']);
        }
    }
}