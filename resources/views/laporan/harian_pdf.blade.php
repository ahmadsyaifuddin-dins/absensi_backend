<!DOCTYPE html>
<html>

<head>
    <title>Laporan Harian</title>
    <style>
        body {
            font-family: sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f2f2f2;
        }

        .meta {
            margin-bottom: 10px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    {{-- 1. Panggil Header Reusable --}}
    @include('laporan._header', ['judul_laporan' => 'LAPORAN KEHADIRAN HARIAN'])

    <div class="meta">
        <strong>Kelas:</strong> {{ $kelas->nama_kelas }} <br>
        <strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Status</th>
                <th>Jam Masuk</th>
                <th>Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item['nisn'] }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td
                        style="
                    color: {{ $item['status'] == 'Alpa' || $item['status'] == 'Belum Absen' ? 'red' : 'black' }};
                    font-weight: bold;">
                        {{ $item['status'] }}
                    </td>
                    <td>{{ $item['jam_masuk'] }}</td>
                    <td>
                        @if ($item['terlambat'])
                            <span style="color: red; font-size: 10px;">(Telat)</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; float: right; text-align: center;">
        <p>Banjarmasin, {{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') }}</p>
        <p>Mengetahui,</p>
        <br><br><br>
        <p><strong>( Guru Piket / Wali Kelas )</strong></p>
    </div>
</body>

</html>
