<?php

namespace App\Http\Controllers;

use App\Models\SeatBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeatBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $bookings = SeatBooking::with('room')
            ->where('user_id', $user->id)
            ->get();

        return response()->json(['success' => true, 'data' => $bookings], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 1. VALIDASI INPUT: Sekarang seat_numbers wajib berbentuk ARRAY
        $validator = Validator::make($request->all(), [
            'room_id'        => 'required|exists:env_rooms,id',
            'seat_numbers'   => 'required|array|min:1', // Harus array dan minimal pilih 1 kursi
            'seat_numbers.*' => 'required|string|max:10', // Tiap item di dalam array harus string
            'booking_date'   => 'required|date|after_or_equal:today',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i|after:start_time',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Tentukan batas awal (Senin) dan batas akhir (Minggu) berdasarkan tanggal booking
        $targetDate = Carbon::parse($request->booking_date);
        $startOfWeek = $targetDate->copy()->startOfWeek()->format('Y-m-d');
        $endOfWeek = $targetDate->copy()->endOfWeek()->format('Y-m-d');

        // ── VALIDASI 1: KUOTA SEMINGGU SEKALI ──
        // Cek apakah user sudah pernah sukses booking di minggu yang sama
        $hasBookedThisWeek = SeatBooking::where('user_id', $user->id)
            ->whereBetween('booking_date', [$startOfWeek, $endOfWeek])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasBookedThisWeek) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda sudah melakukan booking di minggu ini (Maksimal 1 kali transaksi per minggu).'
            ], 422);
        }

        // ── VALIDASI 2: ANTI BENTROK MASSAL (Menggunakan whereIn) ──
        // Cek apakah ada kursi dari daftar yang diminta yang SUDAH TERBOOKING orang lain
        $takenSeats = SeatBooking::where('room_id', $request->room_id)
            ->whereIn('seat_number', $request->seat_numbers) // Mengecek semua kursi sekaligus
            ->where('booking_date', $request->booking_date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                      });
            })
            ->pluck('seat_number') // Ambil list nomor kursi yang bentrok saja
            ->toArray();

        // Jika ada kursi yang sudah diambil, batalkan seluruh proses
        if (!empty($takenSeats)) {
            return response()->json([
                'success' => false,
                'message' => 'Booking gagal. Kursi berikut sudah di-booking orang lain: ' . implode(', ', $takenSeats)
            ], 409); // 409 Conflict
        }

        // ── PROSES INSERT MENGGUNAKAN TRANSACTION ──
        // Menggunakan DB::transaction agar jika salah satu kursi gagal disimpan, semuanya otomatis di-rollback
        $insertedBookings = DB::transaction(function () use ($user, $request) {
            $saved = [];
            foreach ($request->seat_numbers as $seat) {
                $saved[] = SeatBooking::create([
                    'user_id'      => $user->id,
                    'room_id'      => $request->room_id,
                    'seat_number'  => $seat,
                    'booking_date' => $request->booking_date,
                    'start_time'   => $request->start_time,
                    'end_time'     => $request->end_time,
                    'status'       => 'pending'
                ]);
            }
            return $saved;
        });

        return response()->json([
            'success' => true,
            'message' => count($insertedBookings) . ' Kursi berhasil dibooking sekaligus!',
            'data'    => $insertedBookings
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $booking = SeatBooking::where('id', $id)->where('user_id', $user->id)->first();

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Booking kursi berhasil dibatalkan.'], 200);
    }
}