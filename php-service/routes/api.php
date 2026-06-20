<?php

use App\Http\Controllers\EnvRoomController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;

// ── ENDPOINT TELEMETRY — sementara tanpa middleware buat tes dulu ─────────
Route::post('telemetry', [TelemetryController::class, 'store']);

// ── KELOMPOK SEMUA USER (Asalkan Lolos Auth Gateway) ──────────────────────
Route::middleware('gateway.auth')->group(function () {
    
    // Fitur booking kursi untuk user biasa
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
    
    // ── KELOMPOK KHUSUS ADMIN ──────────────────────────────────────────────
    Route::middleware('gateway.role:admin')->group(function () {
        
        Route::apiResource('rooms', EnvRoomController::class);
        
    });

});