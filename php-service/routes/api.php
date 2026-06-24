<?php

use App\Http\Controllers\Admin\EnvRoomController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Users\SeatBookingController;
use App\Http\Controllers\Users\RoomComfortController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\TelemetryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Users\SeatRecommendationController;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'php-service',
    'timestamp' => now()->toISOString(),
]));

Route::post('telemetry', [TelemetryController::class, 'store']);
Route::post('/telemetry/callback', [TelemetryController::class, 'callback']);

Route::post('/room/analyze', [RoomComfortController::class, 'analyze']);

Route::get('/rooms', [RoomComfortController::class, 'index']);
Route::get('/rooms/{id}/comfort', [RoomComfortController::class, 'show']);
Route::get('/rooms/{id}/comfort/history', [RoomComfortController::class, 'history']);

Route::middleware('gateway.auth')->group(function () {
    
    Route::get('bookings', [SeatBookingController::class, 'index']);
    Route::post('bookings', [SeatBookingController::class, 'store']);
    Route::delete('bookings/{id}', [SeatBookingController::class, 'destroy']);
    
    Route::get('rooms/{id}/busy-hour', [EnvRoomController::class, 'predictBusyHour']);
    Route::get('/rooms/{id}/recommend-seats', [SeatRecommendationController::class, 'recommend']);

    Route::middleware('gateway.role:admin')->group(function () {
        
        Route::get('/admin/zones', [ZoneController::class, 'index']);
        Route::post('/admin/zones', [ZoneController::class, 'store']);
        Route::get('/admin/zones/{id}', [ZoneController::class, 'show']);
        Route::put('/admin/zones/{id}', [ZoneController::class, 'update']);
        Route::delete('/admin/zones/{id}', [ZoneController::class, 'destroy']);

        Route::get('/admin/rooms', [RoomController::class, 'index']);
        Route::post('/admin/rooms', [RoomController::class, 'store']);
        Route::get('/admin/rooms/{id}', [RoomController::class, 'show']);
        Route::put('/admin/rooms/{id}', [RoomController::class, 'update']);
        Route::delete('/admin/rooms/{id}', [RoomController::class, 'destroy']);
    });
});