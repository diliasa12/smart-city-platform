<?php

namespace App\Http\Controllers;

use App\Models\EnvRoomTelemetryLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\EnvDeviceCommand;

class TelemetryController extends Controller
{
    public function callback(Request $request) 
{
    // 1. Logika validasi dan update database bawaan kamu yang sudah jalan...
    // (Garis kode kamu yang mengupdate ml_classification_status menjadi 'tidak_nyaman')

    // 2. AMBIL DATA RUANGAN (Sesuaikan dengan variabel di kode kamu)
    // Di sini kita butuh $room_id dan $device_token untuk dikirim ke IoT Service
    $log = EnvRoomTelemetryLog::with('room')->find($request->telemetry_log_id);
    $room = $log->room;

    // 3. INJEKSI LOGIKA OTOMATISASI DI SINI (Tepat sebelum return response)
    if ($room && $room->is_active) {
        if ($request->ml_classification_status === 'tidak_nyaman') {
            // Nyalakan Kipas (Relay) dan Lampu Peringatan (LED) -> Value 1
            $this->dispatchDeviceCommand($room->id, $room->device_token, 'relay', 1);
            $this->dispatchDeviceCommand($room->id, $room->device_token, 'led', 1);
        } else {
            // Matikan jika kondisi kembali normal -> Value 0
            $this->dispatchDeviceCommand($room->id, $room->device_token, 'relay', 0);
            $this->dispatchDeviceCommand($room->id, $room->device_token, 'led', 0);
        }
    }

    // 4. Return response bawaan asli kamu
    return response()->json([
        'meta' => [
            'status' => 'success',
            'message' => 'Hasil prediksi ML berhasil disimpan sebagai hasil akhir.',
            'code' => 200
        ],
        'data' => $log // atau variabel data kamu
    ]);
}

/**
 * Taruh helper function ini di bagian bawah class TelemetryController
 */
private function dispatchDeviceCommand($roomId, $deviceToken, $commandType, $value)
{
    // Simpan history ke database lokal dengan status pending
    $command = EnvDeviceCommand::create([
        'room_id'      => $roomId,
        'command_type' => $commandType,
        'payload'      => ['command' => $commandType, 'value' => $value],
        'status'       => 'pending'
    ]);

    try {
        $iotServiceUrl = env('NODEJS_IOT_SERVER_URL', 'http://iot-service:3000');
        
        // Tembak container Node.js secara internal via network docker
        $response = Http::timeout(3)->post("{$iotServiceUrl}/api/devices/{$deviceToken}/command", [
            'command' => $commandType,
            'value'   => $value
        ]);

        if ($response->successful()) {
            $command->update([
                'status'      => 'sent',
                'executed_at' => Carbon::now()
            ]);
        } else {
            $command->update(['status' => 'failed']);
        }
    } catch (\Exception $e) {
        $command->update(['status' => 'failed']);
    }
}
}