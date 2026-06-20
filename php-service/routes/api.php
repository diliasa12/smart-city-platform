<?php

use App\Http\Controllers\EnvRoomController;
use App\Http\Controllers\SeatBookingController;
use Illuminate\Support\Facades\Route;


Route::middleware('gateway.auth')->group(function () {
    
    
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
  
    
    
    Route::middleware('gateway.role:admin')->group(function () {
        
        Route::apiResource('rooms', EnvRoomController::class);
        
    });

});