<?php

use App\Http\Controllers\EnvRoomController;
use App\Http\Controllers\SeatBookingController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\Admin\RoomController;
use Illuminate\Support\Facades\Route;


<<<<<<< HEAD
// Tanpa Middleware

// Zone CRUD
Route::get('/admin/zones', [ZoneController::class, 'index']);
Route::post('/admin/zones', [ZoneController::class, 'store']);
Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);

// Room CRUD
Route::get('/admin/rooms', [RoomController::class, 'index']);
Route::post('/admin/rooms', [RoomController::class, 'store']);
Route::get('/admin/rooms/{id}', [RoomController::class, 'show']);
Route::put('/admin/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/admin/rooms/{id}', [RoomController::class, 'destroy']);

// ── KELOMPOK SEMUA USER (Asalkan Lolos Auth Gateway) ──────────────────────
=======
>>>>>>> ec726eefe38fd8169b5a4226d6fffa04efc09066
Route::middleware('gateway.auth')->group(function () {
    
    
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
  
    
    
    Route::middleware('gateway.role:admin')->group(function () {
        
        Route::apiResource('rooms', EnvRoomController::class);
        
    });

});