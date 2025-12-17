<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Waktu Server Saat Ini
        $today = Carbon::today(); 

        // 2. Hitung Statistik (Sesuai Logic Kamu)
        $hadir = Absensi::whereDate('tanggal', $today)->where('status', 'Hadir')->count();
        $izin = Absensi::whereDate('tanggal', $today)->where('status', 'Izin')->count();
        $sakit = Absensi::whereDate('tanggal', $today)->where('status', 'Sakit')->count();
        
        // 3. Hitung Alpha
        $totalSiswa = User::where('role', 'siswa')->count();
        $sudahAbsen = $hadir + $izin + $sakit;
        $belumAbsen = $totalSiswa - $sudahAbsen; 

        // 4. Ambil Feed Terakhir
        $terbaru = Absensi::with('user')
            ->whereDate('tanggal', $today)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'message' => 'Data Dashboard Guru',
            'data' => [
                // --- BAGIAN INI UNTUK NGECEK MASALAH TANGGAL ---
                'info_debug' => [
                    'server_time_now' => Carbon::now()->format('Y-m-d H:i:s'), // Jam Detik Server
                    'tanggal_yang_dicari' => $today->format('Y-m-d'), // Tanggal yg dipakai filter
                    'timezone_server' => config('app.timezone'), // Zona waktu yg aktif
                ],
                // ------------------------------------------------
                'statistik' => [
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'belum_absen' => $belumAbsen,
                    'total_siswa' => $totalSiswa
                ],
                'live_feed' => $terbaru
            ]
        ]);
    }
}