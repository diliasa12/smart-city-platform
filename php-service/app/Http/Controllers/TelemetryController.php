<?php

namespace App\Http\Controllers;

use App\Models\EnvRoomTelemetryLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TelemetryController extends Controller
{
    /**
     * Callback endpoint dipanggil oleh ML Service (Python) setelah
     * prediksi selesai. Meng-update record yang sama berdasarkan log_id,
     * lalu mengubah ml_status menjadi 'done'.
     *
     * Ini adalah titik akhir dari alur telemetry — tidak ada proses
     * lanjutan ke client.
     */
    public function callback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'log_id' => 'required|integer|exists:env_room_telemetry_logs,id',
            'ml_classification_status' => 'required|in:nyaman,cukup_nyaman,tidak_nyaman',
            'predicted_next_busy_hour' => 'required|integer|min:0|max:23',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ApiResponse::error('Validasi gagal', 422, $validator->errors()),
                422
            );
        }

        $log = EnvRoomTelemetryLog::find($request->log_id);

        if (!$log) {
            return response()->json(
                ApiResponse::error('Log telemetry tidak ditemukan', 404),
                404
            );
        }

        $log->update([
            'ml_classification_status' => $request->ml_classification_status,
            'predicted_next_busy_hour' => $request->predicted_next_busy_hour,
            'ml_status' => 'done',
        ]);

        Log::info("[Telemetry] Callback diterima untuk log_id={$log->id}, status=done.");

        return response()->json(
            ApiResponse::success($log, 'Hasil prediksi ML berhasil disimpan sebagai hasil akhir.')
        );
    }
}