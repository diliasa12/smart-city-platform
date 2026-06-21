<?php

namespace App\Http\Controllers;

use App\Models\RoomTelemetryLog;
use App\Models\EnvRoom;
use App\Services\RabbitMQPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    private RabbitMQPublisher $publisher;

    public function __construct()
    {
        $this->publisher = new RabbitMQPublisher();
    }

    /**
     * Menerima data sensor mentah dari IoT Service (suhu, kelembaban, kebisingan),
     * menyimpannya sementara ke database, lalu publish event ke RabbitMQ
     * agar diproses oleh Python ML Service.
     *
     * Sesuai alur:
     * 3. PHP ambil data dari IoT service
     * 4. PHP simpan ke database + kirim ke RabbitMQ
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'       => 'required|integer|exists:env_rooms,id',
            'temperature'   => 'required|numeric',
            'humidity'      => 'required|numeric',
            'decibel_level' => 'required|numeric',
        ]);

        // Simpan data mentah ke database (belum ada hasil ML, jadi nilai default 'belum diproses')
        $log = RoomTelemetryLog::create([
            'room_id'                  => $validated['room_id'],
            'temperature'              => $validated['temperature'],
            'humidity'                 => $validated['humidity'],
            'decibel_level'            => $validated['decibel_level'],
            'ml_classification_status' => 'cukup_nyaman',
            'predicted_next_busy_hour' => 0,
        ]);

        // Publish event ke RabbitMQ untuk dikonsumsi Python ML Service
        $this->publisher->publish('telemetry.new', [
            'id'            => $log->id,
            'room_id'       => $log->room_id,
            'temperature'   => $log->temperature,
            'humidity'      => $log->humidity,
            'decibel_level' => $log->decibel_level,
            'timestamp'     => $log->created_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data telemetri berhasil disimpan dan dikirim ke ML.',
            'data'    => $log,
        ], 201);
    }
}