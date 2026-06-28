<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeatBooking;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSeatBookingController extends Controller
{
    /**
     * GET /api/admin/bookings
     * Query params: status, room_id, booking_date
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeatBooking::with(['user', 'room.zone'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('booking_date')) {
            $query->where('booking_date', $request->booking_date);
        }

        $bookings = $query->get()->map(fn($b) => $this->format($b));

        return response()->json(ApiResponse::success($bookings, 'Daftar booking berhasil diambil'));
    }

    /**
     * GET /api/admin/bookings/{id}
     */
    public function show(int $id): JsonResponse
    {
        $booking = SeatBooking::with(['user', 'room.zone'])->find($id);

        if (!$booking) {
            return response()->json(ApiResponse::error('Booking tidak ditemukan', 404), 404);
        }

        return response()->json(ApiResponse::success($this->format($booking), 'Detail booking'));
    }

    /**
     * PATCH /api/admin/bookings/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        $booking = SeatBooking::with(['user', 'room'])->find($id);

        if (!$booking) {
            return response()->json(ApiResponse::error('Booking tidak ditemukan', 404), 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json(
                ApiResponse::error("Hanya booking berstatus 'pending' yang dapat di-approve. Status saat ini: '{$booking->status}'", 422),
                422
            );
        }

        $booking->update(['status' => 'approved']);

        return response()->json(
            ApiResponse::success($this->format($booking->fresh(['user', 'room'])), 'Booking berhasil di-approve')
        );
    }

    /**
     * PATCH /api/admin/bookings/{id}/reject
     */
    public function reject(int $id): JsonResponse
    {
        $booking = SeatBooking::with(['user', 'room'])->find($id);

        if (!$booking) {
            return response()->json(ApiResponse::error('Booking tidak ditemukan', 404), 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json(
                ApiResponse::error("Hanya booking berstatus 'pending' yang dapat di-reject. Status saat ini: '{$booking->status}'", 422),
                422
            );
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(
            ApiResponse::success($this->format($booking->fresh(['user', 'room'])), 'Booking berhasil di-reject')
        );
    }

    /**
     * POST /api/admin/bookings/bulk-approve
     * Body: { "ids": [1, 2, 3] }
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:seat_bookings,id',
        ]);

        $updated = SeatBooking::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        return response()->json(
            ApiResponse::success(['updated_count' => $updated], "{$updated} booking berhasil di-approve")
        );
    }

    /**
     * POST /api/admin/bookings/bulk-reject
     * Body: { "ids": [1, 2, 3] }
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:seat_bookings,id',
        ]);

        $updated = SeatBooking::whereIn('id', $validated['ids'])
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return response()->json(
            ApiResponse::success(['updated_count' => $updated], "{$updated} booking berhasil di-reject")
        );
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function format(SeatBooking $b): array
    {
        return [
            'id'           => $b->id,
            'user'         => [
                'id'    => $b->user->id    ?? null,
                'name'  => $b->user->name  ?? '-',
                'email' => $b->user->email ?? '-',
                'phone' => $b->user->phone ?? '-',
            ],
            'room'         => [
                'id'   => $b->room->id        ?? null,
                'name' => $b->room->room_name ?? '-',
                'zone' => $b->room->zone->name ?? '-',
            ],
            'seat_number'  => $b->seat_number,
            'booking_date' => $b->booking_date,
            'start_time'   => $b->start_time,
            'end_time'     => $b->end_time,
            'status'       => $b->status,
            'created_at'   => $b->created_at,
            'updated_at'   => $b->updated_at,
        ];
    }
}