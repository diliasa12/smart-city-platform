<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EnvRoom;
use App\Models\SeatBooking;
use App\Models\EnvRoomTelemetryLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Halaman utama panel administrator.
     * Menampilkan grafik agregasi kualitas kota dan status IoT.
     * (sesuai spesifikasi: GET /admin/dashboard, Auth: admin)
     */
    public function index(): View
    {
        // 1. Statistik ringkas
        $stats = [
            'total_users'          => User::where('role', 'user')->count(),
            'total_rooms'          => EnvRoom::count(),
            'total_active_rooms'   => EnvRoom::where('is_active', true)->count(),
            'total_bookings_today' => SeatBooking::whereDate('booking_date', now())
                                        ->where('status', '!=', 'cancelled')
                                        ->count(),
        ];

        // 2. Status kenyamanan tiap ruangan (ambil log telemetry PALING BARU per ruangan)
        $rooms = EnvRoom::with('zone')->get();

        $roomStatus = $rooms->map(function ($room) {
            $latest = EnvRoomTelemetryLog::where('room_id', $room->id)
                ->latest('created_at')
                ->first();

            return [
                'room_id'      => $room->id,
                'room_name'    => $room->room_name,
                'zone'         => $room->zone->name ?? null,
                'is_active'    => $room->is_active,
                'comfort'      => $latest->ml_classification_status ?? 'belum_ada_data',
                'temperature'  => $latest->temperature ?? null,
                'humidity'     => $latest->humidity ?? null,
                'decibel'      => $latest->decibel_level ?? null,
                'predicted_busy_hour' => $latest->predicted_next_busy_hour ?? null,
                'last_updated' => $latest->created_at ?? null,
            ];
        });

        // 3. Booking terbaru (5 booking paling baru, semua user)
        $recentBookings = SeatBooking::with(['room', 'user'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'id'           => $booking->id,
                    'user_name'    => $booking->user->name ?? 'Unknown',
                    'room_name'    => $booking->room->room_name ?? 'Unknown',
                    'seat_number'  => $booking->seat_number,
                    'booking_date' => $booking->booking_date,
                    'start_time'   => $booking->start_time,
                    'end_time'     => $booking->end_time,
                    'status'       => $booking->status,
                ];
            });

        return view('admin.dashboard', [
            'stats'           => $stats,
            'roomStatus'      => $roomStatus,
            'recentBookings'  => $recentBookings,
        ]);
    }
}