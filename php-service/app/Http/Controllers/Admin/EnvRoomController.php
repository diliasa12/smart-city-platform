<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\EnvRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\DB;

class EnvRoomController extends Controller
{
    
    public function index(): JsonResponse
    {
        $rooms = EnvRoom::with('zone')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar ruangan berhasil diambil.',
            'data' => $rooms
        ], 200);
    }

    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:shared_zones,id',
            'room_name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:0',
            'device_token' => 'required|string|max:128|unique:env_rooms,device_token',
            'is_active' => 'sometimes|boolean'
        ]);

        $room = EnvRoom::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil ditambahkan.',
            'data' => $room
        ], 201);
    }

    
    public function show(int $id): JsonResponse
    {
        $room = EnvRoom::with('zone')->find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail ruangan ditemukan.',
            'data' => $room
        ], 200);
    }

    
    public function update(Request $request, int $id): JsonResponse
    {
        $room = EnvRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        $validated = $request->validate([
            'zone_id' => 'sometimes|required|integer|exists:shared_zones,id',
            'room_name' => 'sometimes|required|string|max:100',
            'capacity' => 'sometimes|required|integer|min:0',
            'device_token' => [
                'sometimes',
                'required',
                'string',
                'max:128',
                Rule::unique('env_rooms', 'device_token')->ignore($room->id)
            ],
            'is_active' => 'sometimes|boolean'
        ]);

        $room->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data ruangan berhasil diperbarui.',
            'data' => $room
        ], 200);
    }

    
    public function destroy(int $id): JsonResponse
    {
        $room = EnvRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruangan berhasil dihapus.'
        ], 200);
    }

    /**
     * PREDICT BUSY HOUR (Machine Learning Integration)
     * Mengambil data historis decibel 12 jam terakhir, lalu meminta prediksi ke Python ML Service.
     */
   public function predictBusyHour(int $id): JsonResponse
{
    // Cek ketersediaan ruangan
    $room = EnvRoom::find($id);

    if (!$room) {
        return response()->json([
            'success' => false,
            'message' => 'Ruangan tidak ditemukan.'
        ], 404);
    }

    // Ambil telemetry terbaru untuk temperature & humidity
    $latestTelemetry = DB::table('env_room_telemetry_logs')
        ->where('room_id', $id)
        ->orderBy('created_at', 'desc')
        ->first(['temperature', 'humidity', 'decibel_level']);

    if (!$latestTelemetry) {
        return response()->json([
            'success' => false,
            'message' => 'Data telemetry belum tersedia untuk ruangan ini.'
        ], 400);
    }

    // Hitung rata-rata decibel 24 jam terakhir
    $avgDecibel = DB::table('env_room_telemetry_logs')
        ->where('room_id', $id)
        ->where('created_at', '>=', now()->subHours(24))
        ->avg('decibel_level');

    // Fallback ke decibel terbaru jika tidak ada data historis
    $avgDecibel = $avgDecibel 
        ? (float) $avgDecibel 
        : (float) $latestTelemetry->decibel_level;

    try {
      $response = Http::timeout(5)->post(env('ML_SERVICE_URL', 'http://python-ml-service:5000') . '/api/v1/predict-busy-hour', [
            'room_id'       => $id,
            'temperature_c' => (float) $latestTelemetry->temperature,
            'humidity_pct'  => (float) $latestTelemetry->humidity,
            'decibel_level' => $avgDecibel,
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Prediksi jam sibuk berhasil diproses.',
                'data'    => $response->json()
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal memproses prediksi di ML Service.',
            'error'   => $response->json()
        ], $response->status());

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Service ML (Python) tidak dapat dijangkau: ' . $e->getMessage()
        ], 502);
    }
}
}