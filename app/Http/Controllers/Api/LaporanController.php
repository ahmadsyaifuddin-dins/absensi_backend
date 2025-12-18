<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\LaporanPdfTrait; 

class LaporanController extends Controller
{
    // ==========================================
    // (Otomatis method exportHarianPdf, exportBulananPdf, exportSiswaPdf masuk sini)
    // ==========================================
    use LaporanPdfTrait; 

    // ==========================================
    // 1. LAPORAN HARIAN (JSON - Untuk Preview di HP)
    // ==========================================
    public function harian(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $tanggal = $request->tanggal;

        // Ambil semua siswa di kelas tersebut
        $siswa = User::where('role', 'siswa')
                     ->where('kelas_id', $request->kelas_id)
                     ->orderBy('nama', 'asc')
                     ->get();

        $dataLaporan = [];

        foreach ($siswa as $s) {
            // Cek absensi siswa ini pada tanggal tersebut
            $absen = Absensi::where('pengguna_id', $s->id)
                            ->whereDate('tanggal', $tanggal)
                            ->first();

            $status = 'Belum Absen';
            $jam = '-';
            
            if ($absen) {
                $status = $absen->status; // Hadir/Izin/Sakit
                $jam = $absen->jam_masuk ?? '-';
            }

            $dataLaporan[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn_nip,
                'foto_profil' => $s->foto_profil, // Buat nampilin muka di list view HP
                'status' => $status,
                'jam_masuk' => $jam,
                'terlambat' => $absen ? $absen->terlambat : false,
            ];
        }

        return response()->json([
            'message' => 'Laporan Harian',
            'data' => $dataLaporan
        ]);
    }


    // ==========================================
    // 2. REKAP BULANAN (JSON - Untuk Preview di HP)
    // ==========================================
    public function bulanan(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required',
            'bulan' => 'required',
            'tahun' => 'required',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $siswa = User::where('role', 'siswa')
                     ->where('kelas_id', $request->kelas_id)
                     ->orderBy('nama', 'asc')
                     ->get();

        $dataLaporan = [];

        foreach ($siswa as $s) {
            // Hitung statistik menggunakan query agregat biar cepat
            $stats = Absensi::where('pengguna_id', $s->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->selectRaw("
                    count(CASE WHEN status = 'Hadir' THEN 1 END) as hadir,
                    count(CASE WHEN status = 'Izin' THEN 1 END) as izin,
                    count(CASE WHEN status = 'Sakit' THEN 1 END) as sakit,
                    count(CASE WHEN terlambat = 1 THEN 1 END) as telat
                ")
                ->first();

            $dataLaporan[] = [
                'nama' => $s->nama,
                'hadir' => $stats->hadir ?? 0,
                'izin' => $stats->izin ?? 0,
                'sakit' => $stats->sakit ?? 0,
                'telat' => $stats->telat ?? 0,
            ];
        }

        return response()->json(['data' => $dataLaporan]);
    }


    // ==========================================
    // 3. DETAIL ABSENSI / TRACK RECORD (JSON - Preview HP)
    // ==========================================
    public function detailSiswa(Request $request)
    {
        $request->validate(['user_id' => 'required']);

        $history = Absensi::where('pengguna_id', $request->user_id)
                          ->orderBy('tanggal', 'desc')
                          ->get();

        return response()->json(['data' => $history]);
    }


    // ==========================================
    // HELPER: AMBIL SISWA BERDASARKAN KELAS
    // ==========================================
    // Dipakai untuk dropdown di Laporan Siswa
    public function getSiswaByKelas(Request $request)
    {
        $siswa = User::where('kelas_id', $request->kelas_id)
                     ->where('role', 'siswa')
                     ->orderBy('nama', 'asc')
                     ->get();
                     
        return response()->json(['data' => $siswa]);
    }


    // ==========================================
    // 4. LIST PENGAJUAN IZIN (BUTUH VALIDASI)
    // ==========================================
    public function listPengajuanIzin()
    {
        $data = Absensi::with(['user.kelas']) // Load data user & kelasnya
                       ->whereIn('status', ['Izin', 'Sakit'])
                       ->where('validasi', 'Pending') // Hanya yg butuh aksi
                       ->orderBy('tanggal', 'desc')
                       ->get();

        return response()->json(['data' => $data]);
    }


    // ==========================================
    // AKSI: TERIMA / TOLAK IZIN
    // ==========================================
    public function verifikasiIzin(Request $request)
    {
        $request->validate([
            'absensi_id' => 'required',
            'aksi' => 'required|in:Diterima,Ditolak'
        ]);

        $absen = Absensi::find($request->absensi_id);
        if (!$absen) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $absen->update(['validasi' => $request->aksi]);

        return response()->json(['message' => 'Status izin berhasil diubah menjadi ' . $request->aksi]);
    }

    public function rekapIzinJson(Request $request)
    {
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        $kategori = $request->kategori;

        // Base Query: Ambil Diterima DAN Ditolak (Kecuali Pending)
        $query = Absensi::with(['user.kelas'])
            ->whereIn('status', ['Izin', 'Sakit'])
            ->whereIn('validasi', ['Diterima', 'Ditolak'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'desc');
            
        // 1. Filter Kategori (Jika bukan 'Semua')
        if ($kategori && $kategori != 'Semua') {
            $query->where('status', $kategori);
        }

        // 2. Filter Kelas (Hapus juga jika di frontend sudah dihapus, tapi biarkan untuk jaga-jaga)
        if ($request->filled('kelas_id')) {
             $query->whereHas('user', function($q) use ($request) {
                $q->where('kelas_id', $request->kelas_id);
            });
        }

        return response()->json(['data' => $query->get()]);
    }

    // ==========================================
    // 5. LAPORAN KETERLAMBATAN (TOP SKOR)
    // ==========================================
    public function rekapTelat(Request $request)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required'
        ]);

        $data = DB::table('absensi')
            ->join('pengguna', 'absensi.pengguna_id', '=', 'pengguna.id')
            ->join('kelas', 'pengguna.kelas_id', '=', 'kelas.id')
            ->select(
                'pengguna.nama',
                'kelas.nama_kelas',
                DB::raw('count(*) as total_kali_telat'),
                DB::raw('sum(absensi.menit_keterlambatan) as total_menit')
            )
            ->where('absensi.terlambat', true)
            ->whereMonth('absensi.tanggal', $request->bulan)
            ->whereYear('absensi.tanggal', $request->tahun)
            ->groupBy('pengguna.id', 'pengguna.nama', 'kelas.nama_kelas')
            ->orderByDesc('total_menit')
            ->limit(10)
            ->get();

        return response()->json(['data' => $data]);
    }
}