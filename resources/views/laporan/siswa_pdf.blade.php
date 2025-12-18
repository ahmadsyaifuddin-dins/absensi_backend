<!DOCTYPE html>
<html>

<head>
    <title>Laporan Detail Siswa</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data th,
        table.data td {
            border: 1px solid black;
            padding: 6px;
        }

        th {
            background-color: #f0f0f0;
        }

        .info {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    {{-- 1. Panggil Header Reusable --}}
    @include('laporan._header', ['judul_laporan' => 'LAPORAN DETAIL KEHADIRAN SISWA'])

    <div class="info">
        <table style="width: 100%; border: none;">
            <tr>
                <td width="15%" style="border: none;"><strong>Nama</strong></td>
                <td width="35%" style="border: none;">: {{ $user->nama }}</td>
                <td width="15%" style="border: none;"><strong>Kelas</strong></td>
                <td width="35%" style="border: none;">: {{ $user->kelas->nama_kelas ?? '-' }}</td>
            </tr>
            <tr>
                <td style="border: none;"><strong>NISN</strong></td>
                <td style="border: none;">: {{ $user->nisn_nip }}</td>
                <td style="border: none;"><strong>Periode</strong></td>
                <td style="border: none;">: {{ $periode }}</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Hari, Tanggal</th>
                <th>Jam Masuk</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('l, d F Y') }}
                    </td>
                    <td style="text-align: center;">{{ $item->jam_masuk ?? '-' }}</td>
                    <td
                        style="text-align: center; font-weight: bold; 
                    color: {{ $item->status == 'Alpa' || $item->status == 'Belum Absen' ? 'red' : 'black' }}">
                        {{ $item->status }}
                    </td>
                    <td>
                        @if ($item->terlambat)
                            <span style="color: red;">Terlambat {{ $item->menit_keterlambatan }} menit</span>
                        @elseif($item->status == 'Izin' || $item->status == 'Sakit')
                            {{ $item->catatan }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center;">
        <p>Banjarmasin, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}</p>
        <p>Mengetahui,</p>
        <br><br><br>
        <p>_______________________</p>
    </div>
</body>

</html>
