<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EnvironmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// POST dari Gateway (/iot/air -> /api/air)
Route::post('/air', [EnvironmentController::class, 'store']);