<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\EnvRoom;
use App\Models\EnvRoomTelemetryLog;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class RoomComfortController extends Controller
{
    
    public function index(Request $request)
    {
        $zoneId = $request->query('zone_id');

        $query = EnvRoom::with('zone')
            ->where('is_active', true);

        if ($zoneId) {
            $query->where('zone_id', $zoneId);
        }

        $rooms = $query->get();

        $data = $rooms->map(function ($room) {
            $latest = EnvRoomTelemetryLog::where('room_id', $room->id)
                ->where('ml_status', 'done')
                ->latest('created_at')
                ->first();

            return $this->formatRoomComfort($room, $latest);
        });

        return ApiResponse::success($data, 'Daftar ruangan berhasil diambil');
    }

    
    public function show(int $id)
    {
        $room = EnvRoom::with('zone')
            ->where('is_active', true)
            ->find($id);

        if (!$room) {
            return ApiResponse::error('Ruangan tidak ditemukan atau tidak aktif', 404);
        }

        $latest = EnvRoomTelemetryLog::where('room_id', $room->id)
            ->where('ml_status', 'done')
            ->latest('created_at')
            ->first();

        $data = $this->formatRoomComfort($room, $latest);

        return ApiResponse::success($data, 'Status kenyamanan ruangan berhasil diambil');
    }

    
    public function history(int $id)
    {
        $room = EnvRoom::where('is_active', true)->find($id);

        if (!$room) {
            return ApiResponse::error('Ruangan tidak ditemukan atau tidak aktif', 404);
        }

        $logs = EnvRoomTelemetryLog::where('room_id', $id)
            ->where('ml_status', 'done')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($log) => [
                'temperature'             => (float) $log->temperature,
                'humidity'                => (float) $log->humidity,
                'decibel_level'           => (float) $log->decibel_level,
                'comfort_status'          => $log->ml_classification_status,
                'predicted_next_busy_hour'=> $log->predicted_next_busy_hour,
                'recorded_at'             => $log->created_at,
            ]);

        return ApiResponse::success([
            'room_id'   => $room->id,
            'room_name' => $room->room_name,
            'history'   => $logs,
        ], 'Riwayat kenyamanan 24 jam terakhir berhasil diambil');
    }

    

    private function formatRoomComfort(EnvRoom $room, ?EnvRoomTelemetryLog $latest): array
    {
        $comfortLabel = [
            'nyaman'        => 'Nyaman',
            'cukup_nyaman'  => 'Cukup Nyaman',
            'tidak_nyaman'  => 'Tidak Nyaman',
        ];

        return [
            'room_id'      => $room->id,
            'room_name'    => $room->room_name,
            'capacity'     => $room->capacity,
            'zone'         => $room->zone ? [
                'id'           => $room->zone->id,
                'name'         => $room->zone->name,
                'city_district'=> $room->zone->city_district,
            ] : null,
            'comfort'      => $latest ? [
                'status'               => $latest->ml_classification_status,
                'status_label'         => $comfortLabel[$latest->ml_classification_status] ?? '-',
                'temperature'          => (float) $latest->temperature,
                'humidity'             => (float) $latest->humidity,
                'decibel_level'        => (float) $latest->decibel_level,
                'predicted_next_busy_hour' => $latest->predicted_next_busy_hour,
                'last_updated'         => $latest->created_at,
            ] : null,
            'comfort_available' => $latest !== null,
        ];
    }
}