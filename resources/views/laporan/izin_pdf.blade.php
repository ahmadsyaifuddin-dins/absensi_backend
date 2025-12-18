<!DOCTYPE html>
<html>
<head>
    <title>Rekap Izin & Sakit</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    @include('laporan._header', [
        'judul_laporan' => 'REKAPITULASI IZIN & SAKIT',
        'periode' => "Periode: " . $bulan . " " . $tahun
    ])

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th width="10%">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y') }}</td>
                <td class="text-left">{{ $item->user->nama ?? '-' }}</td>
                <td>{{ $item->user->kelas->nama_kelas ?? '-' }}</td>
                <td>
                    <span style="color: {{ $item->status == 'Sakit' ? 'orange' : 'blue' }}">
                        {{ $item->status }}
                    </span>
                </td>
                <td class="text-left">{{ $item->catatan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>