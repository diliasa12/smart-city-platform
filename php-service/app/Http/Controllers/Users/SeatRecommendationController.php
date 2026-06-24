<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\EnvRoom;
use App\Models\EnvRoomTelemetryLog;
use App\Models\SeatBooking;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SeatRecommendationController
 * ==============================
 * GET /api/rooms/{id}/recommend-seats
 *
 * Query params:
 *   booking_date  required  YYYY-MM-DD
 *   start_time    required  HH:MM
 *   end_time      required  HH:MM
 *   seat_count    optional  int (default 1, max 10)
 *   rows          optional  int jumlah baris grid (default auto dari capacity)
 *   cols          optional  int jumlah kolom grid (default auto dari capacity)
 */
class SeatRecommendationController extends Controller
{
    public function recommend(Request $request, int $id)
    {
        // 1. Validasi input
        $validated = $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'seat_count'   => 'sometimes|integer|min:1|max:10',
            'rows'         => 'sometimes|integer|min:1|max:26',
            'cols'         => 'sometimes|integer|min:1|max:20',
        ]);

        // 2. Ambil ruangan
        $room = EnvRoom::with('zone')
            ->where('is_active', true)
            ->find($id);

        if (!$room) {
            return ApiResponse::error('Ruangan tidak ditemukan atau tidak aktif.', 404);
        }

        // 3. Bangku sudah dibooking pada slot yang diminta
        $bookedSeats = SeatBooking::where('room_id', $id)
            ->where('booking_date', $validated['booking_date'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                  ->orWhereBetween('end_time',  [$validated['start_time'], $validated['end_time']])
                  ->orWhere(function ($q2) use ($validated) {
                      $q2->where('start_time', '<=', $validated['start_time'])
                         ->where('end_time',   '>=', $validated['end_time']);
                  });
            })
            ->pluck('seat_number')
            ->toArray();

        // 4. Histori popularitas tiap bangku (30 hari terakhir)
        $popularity = SeatBooking::where('room_id', $id)
            ->where('booking_date', '>=', now()->subDays(30)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->selectRaw('seat_number, COUNT(*) as cnt')
            ->groupBy('seat_number')
            ->pluck('cnt', 'seat_number')
            ->toArray();

        // 5. Generate grid bangku
        $rows  = (int) ($validated['rows'] ?? max(1, (int) ceil($room->capacity / 6)));
        $cols  = (int) ($validated['cols'] ?? min(6, (int) ceil($room->capacity / $rows)));
        $seats = $this->generateGrid($rows, $cols, $bookedSeats, $popularity);

        // 6. Telemetry terbaru
        $log = EnvRoomTelemetryLog::where('room_id', $id)
            ->where('ml_status', 'done')
            ->latest('created_at')
            ->first();

        $telemetry = $log ? [
            'temperature'              => (float) $log->temperature,
            'humidity'                 => (float) $log->humidity,
            'decibel_level'            => (float) $log->decibel_level,
            'ml_classification_status' => $log->ml_classification_status,
            'predicted_next_busy_hour' => $log->predicted_next_busy_hour,
        ] : null;

        // 7. Panggil ML Service
        $payload = [
            'room_id'              => $room->id,
            'room_name'            => $room->room_name,
            'booking_date'         => $validated['booking_date'],
            'start_time'           => $validated['start_time'],
            'end_time'             => $validated['end_time'],
            'seats'                => $seats,
            'telemetry'            => $telemetry,
            'requested_seat_count' => (int) ($validated['seat_count'] ?? 1),
        ];

        try {
            $mlUrl  = rtrim(env('ML_SERVICE_URL', 'http://python-ml:5000'), '/');
            $mlResp = Http::timeout(5)->post("{$mlUrl}/api/v1/recommend-seats", $payload);

            if (!$mlResp->successful()) {
                Log::warning('[SeatRecommendation] ML error', ['status' => $mlResp->status()]);
                return $this->fallback($room, $seats, $telemetry, $validated);
            }

            return ApiResponse::success(
                $this->attachMeta($mlResp->json(), $room, $validated),
                'Rekomendasi bangku berhasil digenerate.'
            );

        } catch (\Throwable $e) {
            Log::error('[SeatRecommendation] ML tidak tersedia: ' . $e->getMessage());
            return $this->fallback($room, $seats, $telemetry, $validated);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function generateGrid(int $rows, int $cols, array $booked, array $popularity): array
    {
        $seats = [];
        for ($r = 0; $r < $rows; $r++) {
            $rowLabel = chr(65 + $r);
            for ($c = 1; $c <= $cols; $c++) {
                $sn = "{$rowLabel}{$c}";
                $seats[] = [
                    'seat_number'              => $sn,
                    'row'                      => $rowLabel,
                    'col'                      => $c,
                    'is_booked'                => in_array($sn, $booked),
                    'booking_count_historical' => $popularity[$sn] ?? 0,
                ];
            }
        }
        return $seats;
    }

    private function attachMeta(array $mlResult, EnvRoom $room, array $validated): array
    {
        return array_merge($mlResult, [
            'room' => [
                'id'       => $room->id,
                'name'     => $room->room_name,
                'zone'     => $room->zone?->name,
                'capacity' => $room->capacity,
            ],
            'slot' => [
                'date'       => $validated['booking_date'],
                'start_time' => $validated['start_time'],
                'end_time'   => $validated['end_time'],
            ],
        ]);
    }

    private function fallback(EnvRoom $room, array $seats, ?array $telemetry, array $validated)
    {
        $available = array_values(array_filter($seats, fn($s) => !$s['is_booked']));
        $count     = (int) ($validated['seat_count'] ?? 1);

        usort($available, fn($a, $b) => $a['booking_count_historical'] <=> $b['booking_count_historical']);
        $recommended = array_column(array_slice($available, 0, $count), 'seat_number');

        $scores = array_map(fn($s) => [
            'seat_number'    => $s['seat_number'],
            'score'          => $s['is_booked'] ? 0.0 : 0.5,
            'is_recommended' => in_array($s['seat_number'], $recommended),
            'reason'         => $s['is_booked'] ? 'sudah dibooking' : 'tersedia',
        ], $seats);

        usort($scores, fn($a, $b) => strcmp($a['seat_number'], $b['seat_number']));

        return ApiResponse::success(
            $this->attachMeta([
                'room_id'           => $room->id,
                'room_name'         => $room->room_name,
                'comfort_level'     => $telemetry['ml_classification_status'] ?? 'unknown',
                'comfort_summary'   => 'Mode fallback — ML Service tidak tersedia.',
                'recommended_seats' => $recommended,
                'seat_scores'       => $scores,
                'available_count'   => count($available),
                'total_seats'       => count($seats),
                'warning'           => 'Rekomendasi tanpa ML scoring karena service tidak tersedia.',
            ], $room, $validated),
            'Rekomendasi bangku (fallback) berhasil digenerate.'
        );
    }
}