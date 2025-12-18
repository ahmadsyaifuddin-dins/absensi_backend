<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IzinController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SekolahController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\LaporanController; 

// --- PUBLIC ROUTES ---
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sekolah', [SekolahController::class, 'index']);


Route::get('/laporan/harian/export', [LaporanController::class, 'exportHarianPdf']);
Route::get('/laporan/bulanan/export', [LaporanController::class, 'exportBulananPdf']);
Route::get('/laporan/siswa/export', [LaporanController::class, 'exportSiswaPdf']); // Public (untuk download)

Route::get('/laporan/izin/export', [LaporanController::class, 'exportIzinPdf']);
Route::get('/laporan/telat/export', [LaporanController::class, 'exportTelatPdf']);

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Profil & Password
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // API Absensi
    Route::post('/absensi', [AbsensiController::class, 'store']);
    Route::get('/absensi/check-today', [AbsensiController::class, 'checkToday']);
    Route::get('/riwayat-absensi', [AbsensiController::class, 'history']);
    Route::post('/absensi/izin', [AbsensiController::class, 'izin']);
    
    // Dashboard Guru
    Route::get('/dashboard/guru', [DashboardController::class, 'index']);

    // Validasi Izin (Guru)
    Route::get('/izin/list', [IzinController::class, 'index']);
    Route::post('/izin/approve/{id}', [IzinController::class, 'update']);

    // Pengaturan Sekolah (Guru)
    Route::post('/sekolah/update', [SekolahController::class, 'update']);

    // Manajemen Siswa (CRUD)
    Route::get('/siswa', [SiswaController::class, 'index']);
    Route::post('/siswa', [SiswaController::class, 'store']);
    Route::post('/siswa/update/{id}', [SiswaController::class, 'update']);
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy']);
    Route::get('/data-kelas', [SiswaController::class, 'getKelas']);

    Route::get('/kelas', [KelasController::class, 'index']);
    Route::post('/kelas', [KelasController::class, 'store']);
    Route::post('/kelas/update/{id}', [KelasController::class, 'update']);
    Route::delete('/kelas/{id}', [KelasController::class, 'destroy']);

    // === ROUTE LAPORAN GURU ===
    Route::get('/laporan/harian', [LaporanController::class, 'harian']);
    Route::get('/laporan/bulanan', [LaporanController::class, 'bulanan']);
    Route::get('/laporan/siswa', [LaporanController::class, 'detailSiswa']);
    Route::get('/laporan/telat', [LaporanController::class, 'rekapTelat']);

    // === ROUTE VALIDASI IZIN ===
    Route::get('/laporan/pengajuan-izin', [LaporanController::class, 'listPengajuanIzin']);
    Route::post('/laporan/verifikasi-izin', [LaporanController::class, 'verifikasiIzin']);

    Route::get('/laporan/rekap-izin', [LaporanController::class, 'rekapIzinJson']);

    Route::get('/list-siswa-by-kelas', [LaporanController::class, 'getSiswaByKelas']); // Buat dropdown
});