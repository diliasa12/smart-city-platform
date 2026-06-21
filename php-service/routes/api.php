<?php

use App\Http\Controllers\EnvRoomController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\RoomController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomComfortController;

// ENDPOINT IOT / SENSOR (Tanpa Auth Gateway)
Route::post('/room/analyze', [RoomComfortController::class, 'analyze']);

// ── KELOMPOK SEMUA USER (Wajib Lolos Auth Gateway) ──────────────────────
Route::middleware('gateway.auth')->group(function () {
    
    // Fitur Booking Kursi
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);

    // Fitur Prediksi Jam Sibuk ML Service (Baru)
    Route::get('rooms/{id}/busy-hour', [EnvRoomController::class, 'predictBusyHour']);

    // ── KELOMPOK KHUSUS ADMIN ───────────────────────────────────────────
    Route::middleware('gateway.role:admin')->group(function () {
        
        // Admin CRUD Wilayah (Zones)
        Route::get('/admin/zones', [ZoneController::class, 'index']);
        Route::post('/admin/zones', [ZoneController::class, 'store']);
        Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
        Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
        Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);

        // Admin CRUD Ruangan (Rooms)
        Route::get('/admin/rooms', [RoomController::class, 'index']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::get('/admin/rooms/{id}', [RoomController::class, 'show']);
        Route::put('/admin/rooms/{id}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{id}', [RoomController::class, 'destroy']);
        
    });
});