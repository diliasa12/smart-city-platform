<?php

use App\Http\Controllers\Admin\EnvRoomController;
use App\Http\Controllers\Users\SeatBookingController;
use App\Http\Controllers\Admin\ZoneController;

use Illuminate\Support\Facades\Route;



Route::middleware('gateway.auth')->group(function () {
    
    
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
  
    
    
    Route::middleware('gateway.role:admin')->group(function () {
        Route::get('/admin/zones', [ZoneController::class, 'index']);
Route::post('/admin/zones', [ZoneController::class, 'store']);
Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);
        Route::apiResource('/admin/rooms', EnvRoomController::class);
        
    });

});