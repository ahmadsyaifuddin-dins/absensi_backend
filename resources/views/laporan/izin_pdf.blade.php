<!DOCTYPE html>
<html>
<head>
    <title>Rekap Izin & Sakit</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 5px; text-align: center; vertical-align: middle; }
        th { background-color: #f0f0f0; }
        .text-left { text-align: left; }
        
        /* Style untuk gambar agar rapi di dalam tabel */
        .img-bukti {
            width: 60px; 
            height: 60px; 
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    @include('laporan._header', [
        'judul_laporan' => $judul_laporan ?? 'REKAPITULASI IZIN & SAKIT (SEMUA KELAS)',
        'periode' => $periode ?? ("Periode: " . $bulan . " " . $tahun)
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
                <th width="15%">Bukti</th> </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->isoFormat('D MMMM Y') }}</td>
                <td class="text-left">{{ $item->user->nama ?? '-' }}</td>
                <td>{{ $item->user->kelas->nama_kelas ?? '-' }}</td>
                <td>
                    <span style="color: {{ $item->status == 'Sakit' ? 'orange' : 'blue' }}; font-weight: bold;">
                        {{ $item->status }}
                    </span>
                </td>
                <td class="text-left">{{ $item->catatan }}</td>
                <td>
                    @if(!empty($item->bukti_izin) && file_exists(public_path($item->bukti_izin)))
                        <img src="{{ public_path($item->bukti_izin) }}" class="img-bukti">
                    @else
                        <span style="color: grey; font-size: 10px;">(Tidak ada)</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div style="margin-top: 30px; float: right; text-align: center;">
        <p>Banjarmasin, {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM Y') }}</p>
        <p>Mengetahui,</p>
        <br><br><br>
        <p>_______________________</p>
    </div>
</body>
</html>