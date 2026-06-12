<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EnvSensorReading;
use App\Models\EnvAlert;

class EnvironmentController extends Controller
{
    public function store(Request $request)
    {
        // 1. Simpan data sensor yang masuk
        $reading = EnvSensorReading::create([
            'zone_id' => $request->zone_id,
            'pm25' => $request->pm25,
            'pm10' => $request->pm10,
            'no2' => $request->no2,
            'co' => $request->co,
            'o3' => $request->o3,
            'temperature' => $request->temperature,
            'humidity' => $request->humidity,
            'sensor_id' => $request->sensor_id,
            'recorded_at' => $request->recorded_at ?: now(),
            'created_at' => now(),
        ]);

        /* 2. Cek apakah ada anomali 
         Ambang batas (threshold) bahaya PM2.5 adalah 150 */
        if ($request->pm25 > 150) {
            EnvAlert::create([
                'zone_id' => $request->zone_id,
                'alert_type' => 'pm25',
                'severity' => 'danger',
                'value' => $request->pm25,
                'threshold' => 150.0,
                'message' => 'Kualitas udara sangat buruk. Kurangi aktivitas luar ruangan.',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data lingkungan berhasil direkam',
            'data' => $reading
        ], 201);
    }
}