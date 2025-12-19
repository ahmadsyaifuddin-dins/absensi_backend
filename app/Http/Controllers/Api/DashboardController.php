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

    // LIHAT DETAIL SISWA BERDASARKAN STATUS
    public function detailStatus(Request $request)
    {
        $today = Carbon::today();
        $status = $request->query('status'); // hadir, sakit, izin, belum

        // Validasi input
        if (!in_array($status, ['hadir', 'sakit', 'izin', 'belum'])) {
            return response()->json(['message' => 'Status tidak valid'], 400);
        }

        $dataSiswa = [];

        if ($status == 'belum') {
            // LOGIC BELUM ABSEN (AGAK TRICKY)
            // 1. Ambil ID siswa yang SUDAH absen hari ini
            $idSudahAbsen = Absensi::whereDate('tanggal', $today)
                                   ->pluck('pengguna_id')
                                   ->toArray();

            // 2. Ambil Siswa yang ID-nya TIDAK ADA di daftar $idSudahAbsen
            $dataSiswa = User::where('role', 'siswa')
                             ->whereNotIn('id', $idSudahAbsen)
                             ->with('kelas') // Load nama kelas
                             ->orderBy('nama', 'asc')
                             ->get();
                             
        } else {
            // LOGIC HADIR / SAKIT / IZIN
            // Ambil dari tabel Absensi, join ke tabel User
            $absensi = Absensi::with(['user', 'user.kelas']) // Load user & kelasnya
                              ->whereDate('tanggal', $today)
                              ->where('status', ucfirst($status)) // "hadir" -> "Hadir"
                              ->orderBy('created_at', 'desc')
                              ->get();
            
            // Format ulang data biar seragam sama format 'belum'
            // Kita extract user-nya saja dari object absensi
            $dataSiswa = $absensi->map(function($item) {
                $user = $item->user;
                // Tempelkan info jam masuk / bukti izin ke object user (opsional, buat info tambahan)
                $user->info_tambahan = $item->jam_masuk ?? $item->catatan; 
                $user->bukti_izin = $item->bukti_izin;
                return $user;
            });
        }

        return response()->json([
            'message' => 'Detail Siswa: ' . ucfirst($status),
            'data' => $dataSiswa
        ]);
    }
}