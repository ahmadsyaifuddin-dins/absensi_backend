<!DOCTYPE html>
<html>

<head>
    <title>Rekap Absensi Bulanan</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
        }

        .text-left {
            text-align: left;
        }
    </style>
</head>

<body>
    {{-- 1. Panggil Header Reusable --}}
    @include('laporan._header', [
        'judul_laporan' => 'REKAPITULASI ABSENSI SISWA',
        'periode' => 'Kelas: ' . $kelas->nama_kelas . ' | Periode: ' . $bulan . ' ' . $tahun,
    ])

    {{-- 2. Tabel Data --}}
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="5%">No</th>
                <th rowspan="2">Nama Siswa</th>
                <th colspan="4">Rekapitulasi</th>
                <th rowspan="2" width="10%">Total<br>Kehadiran</th>
            </tr>
            <tr>
                <th width="10%">Hadir</th>
                <th width="10%">Sakit</th>
                <th width="10%">Izin</th>
                <th width="10%">Alpa /<br>Belum</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item['nama'] }}</td>
                    <td>{{ $item['hadir'] }}</td>
                    <td>{{ $item['sakit'] }}</td>
                    <td>{{ $item['izin'] }}</td>
                    <td style="{{ $item['alpa'] > 0 ? 'color:red; font-weight:bold;' : '' }}">
                        {{ $item['alpa'] }}
                    </td>
                    <td>
                        {{-- Logic Persentase --}}
                        {{ round(($item['hadir'] / max(1, $item['hadir'] + $item['sakit'] + $item['izin'] + $item['alpa'])) * 100) }}%
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 3. Footer Tanda Tangan --}}
    <div style="margin-top: 30px; float: right; text-align: center;">
        {{-- Gunakan now() agar tanggalnya adalah tanggal hari ini (saat PDF dicetak) --}}
        <p>Banjarmasin, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</p>
        <p>Wali Kelas,</p>
        <br><br><br>
        <p>_______________________</p>
    </div>
</body>

</html>
