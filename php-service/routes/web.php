<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AdminAuthController;
use Illuminate\Support\Facades\Route;

// ── LOGIN ADMIN (Guest) ────────────────────────────────────────────────
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

// ── DASHBOARD (sementara tanpa middleware dulu, buat tes) ──────────────
Route::get('/admin/dashboard', [DashboardController::class, 'index']);