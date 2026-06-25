<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login',  [AdminAuthController::class, 'showLoginForm'])->middleware('guest');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('guest');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::post('/admin/logout',   [AdminAuthController::class, 'logout']);
});