<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keterlambatan</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 6px; text-align: center; }
        th { background-color: #f0f0f0; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    @include('laporan._header', [
        'judul_laporan' => 'PERINGKAT KETERLAMBATAN SISWA',
        'periode' => "Periode: " . $bulan . " " . $tahun
    ])

    <table>
        <thead>
            <tr>
                <th width="5%">Peringkat</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th width="15%">Frekuensi<br>Telat</th>
                <th width="15%">Total Waktu<br>(Menit)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left">{{ $item->nama }}</td>
                <td>{{ $item->nama_kelas }}</td>
                <td>{{ $item->total_kali_telat }}x</td>
                <td style="color: red; font-weight: bold;">{{ $item->total_menit }} m</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center;">
        <p>Banjarmasin, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</p>
        <p>Guru BK,</p>
        <br><br><br>
        <p>_______________________</p>
    </div>
</body>
</html>