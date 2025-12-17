<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kelas;

class LaporanController extends Controller
{
    // ==========================================
    // 1. LAPORAN HARIAN (PER KELAS)
    // ==========================================
    // Menampilkan siapa yang Hadir, Izin, Sakit, atau Belum Absen pada tanggal tertentu
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
                'foto_profil' => $s->foto_profil, // Buat nampilin muka di list
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

    public function exportHarianPdf(Request $request)
    {
        // 1. Ambil Data (Copy logic dari fungsi harian tadi)
        $kelasId = $request->kelas_id;
        $tanggal = $request->tanggal;

        $siswa = User::where('role', 'siswa')
                     ->where('kelas_id', $kelasId)
                     ->orderBy('nama', 'asc')
                     ->get();

        $dataLaporan = [];

        foreach ($siswa as $s) {
            $absen = Absensi::where('pengguna_id', $s->id)
                            ->whereDate('tanggal', $tanggal)
                            ->first();

            $status = 'Belum Absen';
            $jam = '-';
            
            if ($absen) {
                $status = $absen->status;
                $jam = $absen->jam_masuk ?? '-';
            }

            $dataLaporan[] = [
                'nama' => $s->nama,
                'nisn' => $s->nisn_nip,
                'status' => $status,
                'jam_masuk' => $jam,
                'terlambat' => $absen ? $absen->terlambat : false,
            ];
        }

        // 2. Ambil Info Kelas (Buat Kop Surat)
        $kelas = Kelas::find($kelasId);

        // 3. Generate PDF
        $pdf = Pdf::loadView('laporan.harian_pdf', [
            'data' => $dataLaporan,
            'kelas' => $kelas,
            'tanggal' => $tanggal
        ]);

        // 4. Stream (Tampilkan di browser) atau Download
        return $pdf->stream('laporan-harian.pdf');
    }

    // ==========================================
    // 2. REKAP BULANAN (PER KELAS)
    // ==========================================
    // Statistik: Si Udin bulan ini Hadir brp kali, Sakit brp kali
    public function bulanan(Request $request)
    {
        $request->validate([
            'kelas_id' => 'required',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer',
        ]);

        $siswa = User::where('role', 'siswa')
                     ->where('kelas_id', $request->kelas_id)
                     ->orderBy('nama', 'asc')
                     ->get();

        $dataLaporan = [];

        foreach ($siswa as $s) {
            // Hitung statistik menggunakan query agregat biar cepat
            $stats = Absensi::where('pengguna_id', $s->id)
                ->whereMonth('tanggal', $request->bulan)
                ->whereYear('tanggal', $request->tahun)
                ->selectRaw("
                    count(CASE WHEN status = 'Hadir' THEN 1 END) as hadir,
                    count(CASE WHEN status = 'Izin' THEN 1 END) as izin,
                    count(CASE WHEN status = 'Sakit' THEN 1 END) as sakit,
                    count(CASE WHEN terlambat = 1 THEN 1 END) as telat
                ")
                ->first();

            $dataLaporan[] = [
                'nama' => $s->nama,
                'hadir' => $stats->hadir,
                'izin' => $stats->izin,
                'sakit' => $stats->sakit,
                'telat' => $stats->telat,
            ];
        }

        return response()->json(['data' => $dataLaporan]);
    }

    // ==========================================
    // 3. DETAIL ABSENSI (PER SISWA)
    // ==========================================
    // Track record satu siswa
    public function detailSiswa(Request $request)
    {
        $request->validate(['user_id' => 'required']);

        $history = Absensi::where('pengguna_id', $request->user_id)
                          ->orderBy('tanggal', 'desc')
                          ->get();

        return response()->json(['data' => $history]);
    }

    // ==========================================
    // 4. LIST PENGAJUAN IZIN (BUTUH VALIDASI)
    // ==========================================
    // Menampilkan daftar siswa yg statusnya Izin/Sakit DAN validasinya masih 'Pending'
    public function listPengajuanIzin()
    {
        $data = Absensi::with(['user.kelas']) // Load data user & kelasnya
                       ->whereIn('status', ['Izin', 'Sakit'])
                       ->where('validasi', 'Pending') // Hanya yg butuh aksi
                       ->orderBy('tanggal', 'desc')
                       ->get();

        return response()->json(['data' => $data]);
    }

    // AKSI: TERIMA / TOLAK IZIN
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

    // ==========================================
    // 5. LAPORAN KETERLAMBATAN (TOP SKOR)
    // ==========================================
    // Siapa raja telat bulan ini?
    public function rekapTelat(Request $request)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required'
        ]);

        // Query agak kompleks: Join user, filter bulan, filter telat, group by user, sum menit
        $data = DB::table('absensi')
            ->join('pengguna', 'absensi.pengguna_id', '=', 'pengguna.id')
            ->join('kelas', 'pengguna.kelas_id', '=', 'kelas.id') // Join kelas buat info
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
            ->orderByDesc('total_menit') // Urutkan dari yg paling lama telatnya
            ->limit(10) // Ambil Top 10 aja biar gak kepanjangan
            ->get();

        return response()->json(['data' => $data]);
    }
}