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
        //  Cek ketersediaan ruangan
        $room = EnvRoom::find($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'Ruangan tidak ditemukan.'
            ], 404);
        }

        // Tarik data historis dari tabel env_room_telemetry_logs (12 jam terakhir) lalu hitung rata-rata decibel per jam
        $historicalData = DB::table('env_room_telemetry_logs')
            ->select(DB::raw('HOUR(created_at) as hour, AVG(decibel_level) as avg_decibel'))
            ->where('room_id', $id)
            ->where('created_at', '>=', now()->subHours(12))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('avg_decibel')
            ->map(fn($val) => (float) $val) // Pastikan float
            ->toArray();

        // Validasi jika data kosong 
        if (empty($historicalData)) {
            return response()->json([
                'success' => false,
                'message' => 'Data historis kebisingan belum mencukupi untuk diprediksi.'
            ], 400);
        }

        try {
            // Hit service Python internal via container name 
            $response = Http::timeout(5)->post('http://python-ml:5000/api/v1/predict-busy-hour', [
                'room_id' => $id,
                'historical_decibels' => $historicalData
            ]);

            // Kembalikan respons sesuai hasil dari Python
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Prediksi jam sibuk berhasil diproses.',
                    'data' => $response->json()
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses prediksi di ML Service.',
                'error' => $response->json()
            ], $response->status());

        } catch (\Exception $e) {
            // Tangkap error jika container Python mati atau tidak bisa diakses
            return response()->json([
                'success' => false,
                'message' => 'Service ML (Python) tidak dapat dijangkau: ' . $e->getMessage()
            ], 502); 
        }
    }
}