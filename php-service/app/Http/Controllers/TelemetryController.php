<?php

namespace App\Http\Controllers;

use App\Models\EnvRoomTelemetryLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\EnvDeviceCommand;

class TelemetryController extends Controller
{
    // ✅ TAMBAHAN: method store() yang hilang tapi ada di route
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

        return ApiResponse::success($log, 'Data telemetry diterima', 201);
    }

    public function callback(Request $request)
    {
        // ✅ PERBAIKAN: validasi input sebelum digunakan
        $validated = $request->validate([
            'telemetry_log_id'        => 'required|integer|exists:env_room_telemetry_logs,id',
            'ml_classification_status'=> 'required|in:nyaman,cukup_nyaman,tidak_nyaman',
            'predicted_next_busy_hour'=> 'required|integer|min:0|max:23',
        ]);

        $log = EnvRoomTelemetryLog::with('room')->find($validated['telemetry_log_id']);

        // ✅ PERBAIKAN: update semua kolom hasil ML
        $log->update([
            'ml_classification_status' => $validated['ml_classification_status'],
            'predicted_next_busy_hour' => $validated['predicted_next_busy_hour'],
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