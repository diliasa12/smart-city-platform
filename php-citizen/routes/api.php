<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CitizenController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotifController;
use App\Http\Controllers\HealthController;

// Health check — tidak perlu auth
Route::get('/health', [HealthController::class, 'index']);
Route::get('/', function () {
    return response()->json(['message' => 'Hello World!']);
});
// Semua route di bawah ini perlu header dari Gateway
Route::middleware('gateway.auth')->group(function () {

    // Citizen
    Route::post('/citizens', [CitizenController::class, 'store']);
    Route::get('/citizens/{id}', [CitizenController::class, 'show']);

    // Reports
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::patch('/reports/{id}/status', [ReportController::class, 'updateStatus']);

    // Notifications
    Route::get('/notifications', [NotifController::class, 'index']);
});
