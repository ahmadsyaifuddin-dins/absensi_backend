<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    // ======================================================
    // 1. FUNGSI CHECK-IN (ABSEN MASUK)
    // ======================================================
    public function store(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // A. CEK HARI SABTU & MINGGU (FIXED)
        // Logic: Jika Hari Weekend DAN Bypass-nya TIDAK aktif, maka tolak.
        if ($today->isWeekend() && env('BYPASS_WEEKEND') != true) {
            return response()->json([
                'message' => 'Hari ini akhir pekan (Sabtu/Minggu), sekolah libur!',
            ], 400);
        }

        // B. CEK HARI LIBUR DINAMIS (DATABASE)
        $libur = HariLibur::where('tanggal', $today->toDateString())->first();
        if ($libur) {
            return response()->json([
                'message' => 'Sekolah Libur: '.$libur->keterangan,
            ], 400);
        }

        // 2. Cek apakah sudah absen hari ini?
        $cekAbsen = Absensi::where('pengguna_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($cekAbsen) {
            return response()->json([
                'message' => 'Kamu sudah melakukan absensi hari ini!',
            ], 400);
        }

        // 3. Validasi Input
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'foto' => 'required|image|max:2048', // Maks 2MB
        ]);

        // 4. Ambil Setting Sekolah
        $setting = PengaturanSekolah::first();
        if (! $setting) {
            return response()->json(['message' => 'Konfigurasi sekolah belum diatur admin!'], 500);
        }

        // 5. Hitung Jarak (METER)
        $jarakMeter = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $setting->latitude,
            $setting->longitude
        );

        $jarakMeter = round($jarakMeter);
        $batasRadius = $setting->radius_meter;

        // Cek Radius
        if ($jarakMeter > $batasRadius) {
            return response()->json([
                'message' => "Jarak terlalu jauh! Kamu berjarak $jarakMeter meter dari sekolah. (Maks: $batasRadius meter)",
            ], 400);
        }

        // 6. Cek Keterlambatan
        $jamSekarang = Carbon::now()->format('H:i:s');
        $isLate = false;
        $lateDuration = 0;

        if ($jamSekarang > $setting->jam_masuk) {
            $isLate = true;
            $waktuMasuk = Carbon::parse($setting->jam_masuk);
            $waktuSekarang = Carbon::parse($jamSekarang);
            $lateDuration = $waktuMasuk->diffInMinutes($waktuSekarang);
        }

        // 7. Simpan Foto
        $file = $request->file('foto');
        $namaFile = time().'_'.Str::random(10).'.jpg';
        $file->move(public_path('absensi'), $namaFile);
        $fotoPath = 'absensi/'.$namaFile;

        // 8. SIMPAN KE DATABASE
        $absensi = Absensi::create([
            'pengguna_id' => $user->id,
            'tanggal' => $today,
            'jam_masuk' => $jamSekarang,
            'latitude_masuk' => $request->latitude,
            'longitude_masuk' => $request->longitude,
            'foto_masuk' => $fotoPath,
            'status' => 'Hadir',
            'terlambat' => $isLate,
            'menit_keterlambatan' => $lateDuration,
        ]);

        return response()->json([
            'message' => 'Absensi Berhasil!',
            'data' => $absensi,
        ], 201);
    }

    // ======================================================
    // 2. CEK STATUS HARI INI (UNTUK HOME SCREEN APP)
    // ======================================================
    public function checkToday(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // --- LOGIC STATUS LIBUR UNTUK UI ---
        $isHoliday = false;
        $holidayMessage = '';

        // 1. Cek Weekend
        if ($today->isWeekend()) {
            $isHoliday = true;
            $holidayMessage = 'Libur Akhir Pekan';
        } else {
            // 2. Cek Database Libur
            $libur = HariLibur::where('tanggal', $today->toDateString())->first();
            if ($libur) {
                $isHoliday = true;
                $holidayMessage = $libur->keterangan;
            }
        }

        // Cek Data Absen
        $absen = Absensi::where('pengguna_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $user->load('kelas');
        $namaKelas = $user->kelas ? $user->kelas->nama_kelas : 'Belum ada kelas';

        return response()->json([
            'message' => 'Status absen hari ini',
            'data' => [
                'sudah_absen' => $absen ? true : false,
                'jam_absen' => $absen ? $absen->jam_masuk : null,
                'nama_kelas' => $namaKelas,
                // Kirim info libur ke Frontend
                'is_holiday' => $isHoliday,
                'holiday_message' => $holidayMessage,
            ],
        ]);
    }

    // ======================================================
    // 3. RIWAYAT ABSENSI
    // ======================================================
    public function history(Request $request)
    {
        $history = Absensi::where('pengguna_id', $request->user()->id)
            ->orderBy('tanggal', 'desc')
            ->take(30)
            ->get();

        return response()->json([
            'message' => 'Data riwayat berhasil diambil',
            'data' => $history,
        ]);
    }

    // ======================================================
    // 4. PENGAJUAN IZIN / SAKIT
    // ======================================================
    public function izin(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // Cek Libur juga disini (Masa izin pas hari libur?)
        if ($today->isWeekend() || HariLibur::where('tanggal', $today->toDateString())->exists()) {
            return response()->json([
                'message' => 'Hari ini libur, tidak perlu mengajukan izin.',
            ], 400);
        }

        $cekAbsen = Absensi::where('pengguna_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if ($cekAbsen) {
            return response()->json([
                'message' => 'Anda sudah mengisi daftar hadir hari ini!',
            ], 400);
        }

        $request->validate([
            'status' => 'required|in:Izin,Sakit',
            'catatan' => 'required|string',
            'bukti_izin' => 'required|image|max:2048',
        ]);

        $file = $request->file('bukti_izin');
        $namaFile = time().'_'.Str::random(10).'.jpg';
        $file->move(public_path('izin'), $namaFile);
        $fotoPath = 'izin/'.$namaFile;

        $absensi = Absensi::create([
            'pengguna_id' => $user->id,
            'tanggal' => $today,
            'status' => $request->status,
            'catatan' => $request->catatan,
            'bukti_izin' => $fotoPath,
            'jam_masuk' => null,
            'foto_masuk' => null,
            'terlambat' => false,
        ]);

        return response()->json([
            'message' => 'Pengajuan izin berhasil dikirim.',
            'data' => $absensi,
        ], 201);
    }

    // LOGIC HITUNG JARAK (Meter)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Radius Bumi ke METER
        $earthRadius = 6371000; // 6371 KM * 1000

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance;
    }
}
