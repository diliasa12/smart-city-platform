<?php

namespace App\Console\Commands;

use App\Models\EnvRoom;
use App\Models\EnvRoomTelemetryLog;
use App\Services\IotServiceClient;
use App\Services\RabbitMQPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * PullAndDispatchTelemetry
 *
 * Dijalankan tiap 1 menit oleh Laravel Scheduler.
 *
 * Alur:
 *  1. Ambil daftar device aktif dari IoT Service.
 *  2. Untuk setiap device, ambil data sensor terbaru.
 *  3. Simpan ke DB dengan ml_status = pending.
 *  4. Publish payload ke RabbitMQ, lalu update ml_status = queued.
 *
 * Error pada satu device TIDAK menghentikan proses device lain.
 */
class PullAndDispatchTelemetry extends Command
{
    protected $signature = 'telemetry:pull-and-dispatch';

    protected $description = 'Ambil data sensor dari IoT Service, simpan ke DB, lalu publish ke RabbitMQ untuk diproses ML Service';

    public function handle(IotServiceClient $iot): int
    {
        $this->info('[Telemetry] Mulai pull & dispatch...');

        $devices = $iot->getDevices();

        if (empty($devices)) {
            $this->warn('[Telemetry] Tidak ada device aktif yang ditemukan di IoT Service.');
            return self::SUCCESS;
        }

        $publisher = new RabbitMQPublisher();

        $success = 0;
        $failed = 0;

        foreach ($devices as $deviceToken) {
            try {
                $room = EnvRoom::where('device_token', $deviceToken)
                    ->where('is_active', true)
                    ->first();

                if (!$room) {
                    Log::info("[Telemetry] device_token={$deviceToken} tidak terdaftar/non-aktif di env_rooms, skip.");
                    continue;
                }

                $sensorData = $iot->getDeviceData($deviceToken);

                if (!$sensorData) {
                    Log::warning("[Telemetry] Tidak ada data sensor untuk device_token={$deviceToken}, skip.");
                    $failed++;
                    continue;
                }

                // 2. Simpan ke DB dengan status pending
                $log = EnvRoomTelemetryLog::create([
                    'room_id' => $room->id,
                    'temperature' => $sensorData['suhu'] ?? $sensorData['temperature'] ?? 0,
                    'humidity' => $sensorData['kelembaban'] ?? $sensorData['humidity'] ?? 0,
                    'decibel_level' => $sensorData['kebisingan'] ?? $sensorData['decibel_level'] ?? 0,
                    'ml_status' => 'pending',
                    'ml_classification_status' => null,
                    'predicted_next_busy_hour' => null,
                ]);

                // 3. Publish ke RabbitMQ
                $published = $publisher->publishTelemetry([
                    'log_id' => $log->id,
                    'room_id' => $room->id,
                    'temperature' => (float) $log->temperature,
                    'humidity' => (float) $log->humidity,
                    'decibel_level' => (float) $log->decibel_level,
                ]);

                if ($published) {
                    $log->update(['ml_status' => 'queued']);
                    $success++;
                } else {
                    $log->update(['ml_status' => 'failed']);
                    $failed++;
                }
            } catch (\Throwable $e) {
                // Tangani error per device agar 1 device gagal tidak menghentikan device lain
                Log::error("[Telemetry] Error memproses device_token={$deviceToken}: {$e->getMessage()}");
                $failed++;
                continue;
            }
        }

        $publisher->close();

        $this->info("[Telemetry] Selesai. Berhasil: {$success}, Gagal: {$failed}");

        return self::SUCCESS;
    }
}