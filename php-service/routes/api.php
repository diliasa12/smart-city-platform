<?php

use App\Http\Controllers\Admin\EnvRoomController;
use App\Http\Controllers\Users\SeatBookingController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

// ── ENDPOINT TELEMETRY — sementara tanpa middleware buat tes dulu ─────────
Route::post('telemetry', [TelemetryController::class, 'store']);

Route::middleware('gateway.auth')->group(function () {
    
    // Fitur booking kursi untuk user biasa
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
    
    // ── KELOMPOK KHUSUS ADMIN ──────────────────────────────────────────────
    Route::middleware('gateway.role:admin')->group(function () {
        Route::get('/admin/zones', [ZoneController::class, 'index']);
        Route::post('/admin/zones', [ZoneController::class, 'store']);
        Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
        Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
        Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);
        Route::apiResource('/admin/rooms', EnvRoomController::class);
        
    });

});