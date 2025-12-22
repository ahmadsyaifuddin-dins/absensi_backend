<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AdminGuruController;
use App\Http\Controllers\Api\AdminHariLiburController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SekolahController;
use App\Http\Controllers\Api\SiswaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- PUBLIC ROUTES ---
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sekolah', [SekolahController::class, 'index']);

// Export PDF (Bypass Auth untuk Browser)
Route::get('/laporan/harian/export', [LaporanController::class, 'exportHarianPdf']);
Route::get('/laporan/bulanan/export', [LaporanController::class, 'exportBulananPdf']);
Route::get('/laporan/siswa/export', [LaporanController::class, 'exportSiswaPdf']);
Route::get('/laporan/telat/export', [LaporanController::class, 'exportTelatPdf']);
// Kita modifikasi route izin ini agar bisa menerima filter status
Route::get('/laporan/izin/export', [LaporanController::class, 'exportIzinPdf']);

// --- PROTECTED ROUTES (Butuh Token) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // AREA ADMIN (KELOLA GURU)
    // (opsional: bisa tambah middleware checkRole)
    Route::get('/admin/guru', [AdminGuruController::class, 'index']);      // List Guru
    Route::post('/admin/guru', [AdminGuruController::class, 'store']);     // Tambah Guru
    Route::post('/admin/guru/{id}', [AdminGuruController::class, 'update']); // Edit Guru
    Route::delete('/admin/guru/{id}', [AdminGuruController::class, 'destroy']); // Hapus Guru

    // Profil & Auth
    Route::post('/profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // API Absensi (Siswa)
    Route::post('/absensi', [AbsensiController::class, 'store']);
    Route::get('/absensi/check-today', [AbsensiController::class, 'checkToday']);
    Route::get('/riwayat-absensi', [AbsensiController::class, 'history']);
    Route::post('/absensi/izin', [AbsensiController::class, 'izin']);

    // Dashboard Guru
    Route::get('/dashboard/guru', [DashboardController::class, 'index']);
    Route::get('/dashboard/detail-status', [DashboardController::class, 'detailStatus']);

    // --- MANAJEMEN DATA (GURU) ---
    // Sekolah
    Route::post('/sekolah/update', [SekolahController::class, 'update']);

    // Siswa
    Route::get('/siswa', [SiswaController::class, 'index']);
    Route::post('/siswa', [SiswaController::class, 'store']);
    Route::post('/siswa/update/{id}', [SiswaController::class, 'update']);
    Route::delete('/siswa/{id}', [SiswaController::class, 'destroy']);
    Route::get('/data-kelas', [SiswaController::class, 'getKelas']);

    // Kelas
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::post('/kelas', [KelasController::class, 'store']);
    Route::post('/kelas/update/{id}', [KelasController::class, 'update']);
    Route::delete('/kelas/{id}', [KelasController::class, 'destroy']);

    // --- PUSAT LAPORAN & VALIDASI (GURU) ---
    // Laporan View JSON
    Route::get('/laporan/harian', [LaporanController::class, 'harian']);
    Route::get('/laporan/bulanan', [LaporanController::class, 'bulanan']);
    Route::get('/laporan/siswa', [LaporanController::class, 'detailSiswa']);
    Route::get('/laporan/telat', [LaporanController::class, 'rekapTelat']);
    Route::get('/laporan/rekap-izin', [LaporanController::class, 'rekapIzinJson']); // History Izin

    Route::get('/admin/hari-libur', [AdminHariLiburController::class, 'index']);
    Route::post('/admin/hari-libur', [AdminHariLiburController::class, 'store']);
    Route::delete('/admin/hari-libur/{id}', [AdminHariLiburController::class, 'destroy']);

    // Validasi Izin (Pengganti IzinController)
    // 1. List data pending
    Route::get('/laporan/pengajuan-izin', [LaporanController::class, 'listPengajuanIzin']);
    // 2. Aksi Terima/Tolak
    Route::post('/laporan/verifikasi-izin', [LaporanController::class, 'verifikasiIzin']);

    // Helper Dropdown
    Route::get('/list-siswa-by-kelas', [LaporanController::class, 'getSiswaByKelas']);
});
