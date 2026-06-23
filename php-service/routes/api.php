<?php

use App\Http\Controllers\Admin\EnvRoomController;
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

// ── ENDPOINT PUBLIK — tidak butuh login ───────────────────────────────────
// User bisa lihat daftar & status kenyamanan ruangan sebelum login/booking
Route::get('/rooms', [RoomComfortController::class, 'index']);
Route::get('/rooms/{id}/comfort', [RoomComfortController::class, 'show']);
Route::get('/rooms/{id}/comfort/history', [RoomComfortController::class, 'history']);

// ── ENDPOINT TERPROTEKSI — wajib lewat API Gateway ────────────────────────
Route::middleware('gateway.auth')->group(function () {

    // Booking kursi untuk user
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);

    // ── KHUSUS ADMIN ──────────────────────────────────────────────────────
    Route::middleware('gateway.role:admin')->group(function () {
        // Zona
        Route::get('/admin/zones', [ZoneController::class, 'index']);
        Route::post('/admin/zones', [ZoneController::class, 'store']);
        Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
        Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
        Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);

        // Ruangan
        Route::apiResource('/admin/rooms', EnvRoomController::class);
    });
});