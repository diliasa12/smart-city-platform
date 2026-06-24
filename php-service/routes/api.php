<?php

use App\Http\Controllers\Admin\EnvRoomController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Users\SeatBookingController;
use App\Http\Controllers\Users\RoomComfortController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

// ── HEALTH CHECK ──────────────────────────────────────────────────────────
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'php-service',
    'timestamp' => now()->toISOString(),
]));

// ── ENDPOINT TELEMETRY (internal, dipanggil IoT/ML service) ──────────────
Route::post('telemetry', [TelemetryController::class, 'store']);
Route::post('/telemetry/callback', [TelemetryController::class, 'callback']);

// ENDPOINT IOT / SENSOR (Tanpa Auth Gateway)
Route::post('/room/analyze', [RoomComfortController::class, 'analyze']);

// ── ENDPOINT PUBLIK — tidak butuh login ───────────────────────────────────
// User bisa lihat daftar & status kenyamanan ruangan sebelum login/booking
Route::get('/rooms', [RoomComfortController::class, 'index']);
Route::get('/rooms/{id}/comfort', [RoomComfortController::class, 'show']);
Route::get('/rooms/{id}/comfort/history', [RoomComfortController::class, 'history']);

// ── KELOMPOK SEMUA USER (Wajib Lolos Auth Gateway) ──────────────────────
// ── ENDPOINT TERPROTEKSI — wajib lewat API Gateway ────────────────────────
Route::middleware('gateway.auth')->group(function () {
    
    // Fitur Booking Kursi
    // Booking kursi untuk user
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);

    // Fitur Prediksi Jam Sibuk ML Service (Baru)
    Route::get('rooms/{id}/busy-hour', [EnvRoomController::class, 'predictBusyHour']);

    // ── KELOMPOK KHUSUS ADMIN ───────────────────────────────────────────
    // ── KHUSUS ADMIN ──────────────────────────────────────────────────────
    Route::middleware('gateway.role:admin')->group(function () {
        
        // Admin CRUD Wilayah (Zones)
        // Zona
        Route::get('/admin/zones', [ZoneController::class, 'index']);
        Route::post('/admin/zones', [ZoneController::class, 'store']);
        Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
        Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
        Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);

        // Admin CRUD Ruangan (Rooms)
        // Ruangan
        Route::get('/admin/rooms', [RoomController::class, 'index']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::get('/admin/rooms/{id}', [RoomController::class, 'show']);
        Route::put('/admin/rooms/{id}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{id}', [RoomController::class, 'destroy']);
        
    });
});