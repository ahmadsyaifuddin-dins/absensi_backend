<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\PengaturanSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsensiController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // 1. Cek Hari Libur
        $isHoliday = HariLibur::where('tanggal', $today->toDateString())->exists();
        if ($isHoliday) {
            return response()->json([
                'message' => 'Hari ini libur, tidak bisa absen!',
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

        // Bulatkan jarak biar rapi
        $jarakMeter = round($jarakMeter);
        $batasRadius = $setting->radius_meter; // Ambil dari DB (integer)

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

        // Bandingkan jam sekarang dengan jam masuk
        if ($jamSekarang > $setting->jam_masuk) {
            $isLate = true;
            $waktuMasuk = Carbon::parse($setting->jam_masuk);
            $waktuSekarang = Carbon::parse($jamSekarang);
            $lateDuration = $waktuMasuk->diffInMinutes($waktuSekarang);
        }

        // 7. Simpan Foto
        $file = $request->file('foto');
        $namaFile = time() . '_' . Str::random(10) . '.jpg';
        $file->move(public_path('absensi'), $namaFile);
        $fotoPath = 'absensi/' . $namaFile;

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

    public function izin(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

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
        $namaFile = time() . '_' . Str::random(10) . '.jpg';
        $file->move(public_path('izin'), $namaFile);
        $fotoPath = 'izin/' . $namaFile;

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

    public function checkToday(Request $request)
    {
        $user = $request->user();
        
        $today = Carbon::today();
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
            ]
        ]);
    }

    // LOGIC MENGHITUNG JARAK (METER)
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // 1. Ubah Radius Bumi ke METER
        $earthRadius = 6371000; // 6371 KM * 1000

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Hasil langsung dalam Meter

        return $distance;
    }
}