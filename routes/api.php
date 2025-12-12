<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- PUBLIC ROUTES (Bisa diakses tanpa Token) ---
Route::post('/login', [AuthController::class, 'login']);

// --- PROTECTED ROUTES (Harus pakai Token / Sudah Login) ---
Route::middleware('auth:sanctum')->group(function () {

    // Test User Profile (Cek siapa yang login)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // NANTI KITA TAMBAH ROUTE ABSENSI DI SINI
});
