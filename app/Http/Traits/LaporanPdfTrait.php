<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;

trait LaporanPdfTrait
{
    // Pindahkan exportHarianPdf kesini
    public function exportHarianPdf(Request $request)
    {
        $kelasId = $request->kelas_id;
        $tanggal = $request->tanggal;

        $siswa = User::where('role', 'siswa')->where('kelas_id', $kelasId)->orderBy('nama', 'asc')->get();
        $dataLaporan = [];

        foreach ($siswa as $s) {
            $absen = Absensi::where('pengguna_id', $s->id)->whereDate('tanggal', $tanggal)->first();
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

        $kelas = Kelas::find($kelasId);
        $pdf = Pdf::loadView('laporan.harian_pdf', [
            'data' => $dataLaporan,
            'kelas' => $kelas,
            'tanggal' => $tanggal
        ]);
        return $pdf->stream('laporan-harian.pdf');
    }

    // Pindahkan exportBulananPdf kesini
    public function exportBulananPdf(Request $request)
    {
        $kelasId = $request->kelas_id;
        $bulan = (int) $request->bulan; // Type casting aman
        $tahun = (int) $request->tahun;

        $siswa = User::where('role', 'siswa')->where('kelas_id', $kelasId)->orderBy('nama', 'asc')->get();
        $dataLaporan = [];

        foreach ($siswa as $s) {
            $stats = Absensi::where('pengguna_id', $s->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->selectRaw("
                    count(CASE WHEN status = 'Hadir' THEN 1 END) as hadir,
                    count(CASE WHEN status = 'Izin' THEN 1 END) as izin,
                    count(CASE WHEN status = 'Sakit' THEN 1 END) as sakit
                ")
                ->first();
            
            $alpa = Absensi::where('pengguna_id', $s->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->whereIn('status', ['Alpa', 'Belum Absen'])
                ->count();

            $dataLaporan[] = [
                'nama' => $s->nama,
                'hadir' => $stats->hadir ?? 0,
                'izin' => $stats->izin ?? 0,
                'sakit' => $stats->sakit ?? 0,
                'alpa' => $alpa
            ];
        }

        $kelas = Kelas::find($kelasId);
        $namaBulan = Carbon::create()->month($bulan)->locale('id')->translatedFormat('F');

        $pdf = Pdf::loadView('laporan.bulanan_pdf', [
            'data' => $dataLaporan,
            'kelas' => $kelas,
            'bulan' => $namaBulan,
            'tahun' => $tahun
        ]);
        return $pdf->stream('rekap-bulanan.pdf');
    }

    // Pindahkan exportSiswaPdf kesini
    public function exportSiswaPdf(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'bulan' => 'required',
            'tahun' => 'required'
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $user = User::with('kelas')->find($request->user_id);
        
        $data = Absensi::where('pengguna_id', $request->user_id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal', 'asc')
            ->get();

        $namaBulan = Carbon::create()->month($bulan)->locale('id')->translatedFormat('F');
        $periode = "$namaBulan " . $tahun;

        $pdf = Pdf::loadView('laporan.siswa_pdf', [
            'data' => $data,
            'user' => $user,
            'periode' => $periode
        ]);

        return $pdf->stream('laporan-siswa-' . ($user->nisn_nip ?? 'export') . '.pdf');
    }

    // 4. EXPORT PDF REKAP IZIN (HISTORY)
    public function exportIzinPdf(Request $request)
    {
        // Filter Wajib
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;
        
        // Filter Opsional
        $kelasId = $request->kelas_id;
        $kategori = $request->kategori; // 'Semua', 'Sakit', atau 'Izin'

        // Query: Ambil Diterima DAN Ditolak (Agar history lengkap seperti di JSON)
        $query = Absensi::with(['user.kelas'])
            ->whereIn('status', ['Izin', 'Sakit'])
            ->whereIn('validasi', ['Diterima', 'Ditolak'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        // Filter Kategori (Sakit / Izin)
        if ($kategori && $kategori != 'Semua') {
            $query->where('status', $kategori);
        }

        // Filter Kelas (Hanya jika dikirim dan valid)
        if ($kelasId && $kelasId != 'null') {
            $query->whereHas('user', function($q) use ($kelasId) {
                $q->where('kelas_id', $kelasId);
            });
        }
        
        $data = $query->orderBy('tanggal', 'asc')->get();

        // Judul Dinamis
        $judul = 'REKAPITULASI IZIN & SAKIT (SEMUA KELAS)';
        if ($kategori == 'Sakit') $judul = 'LAPORAN SISWA SAKIT';
        if ($kategori == 'Izin') $judul = 'LAPORAN SISWA IZIN';

        $namaBulan = Carbon::create()->month($bulan)->locale('id')->translatedFormat('F');

        $pdf = Pdf::loadView('laporan.izin_pdf', [
            'data' => $data,
            'judul_laporan' => $judul,
            'periode' => "Periode: $namaBulan $tahun",
            'bulan' => $namaBulan, 
            'tahun' => $tahun
        ]);

        return $pdf->stream('laporan-rekap-izin.pdf');
    }

    public function exportTelatPdf(Request $request)
    {
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        // Query Ranking Telat (Sama seperti JSON tapi untuk PDF)
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
            ->whereMonth('absensi.tanggal', $bulan)
            ->whereYear('absensi.tanggal', $tahun)
            ->groupBy('pengguna.id', 'pengguna.nama', 'kelas.nama_kelas')
            ->orderByDesc('total_menit')
            ->limit(20) // Ambil Top 20 untuk dicetak
            ->get();

        $namaBulan = Carbon::create()->month($bulan)->locale('id')->translatedFormat('F');

        $pdf = Pdf::loadView('laporan.telat_pdf', [
            'data' => $data,
            'bulan' => $namaBulan,
            'tahun' => $tahun
        ]);

        return $pdf->stream('laporan-keterlambatan.pdf');
    }
}