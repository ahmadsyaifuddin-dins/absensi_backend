<table
    style="width: 100%; border-bottom: 2px solid black; padding-bottom: 10px; margin-bottom: 20px; border-collapse: collapse;">
    <tr>
        <td style="width: 15%; text-align: center; border: none;">
            {{-- Pastikan file logo.png ada di folder public/ --}}
            <img src="{{ public_path('logo.png') }}" style="width: 70px; height: auto;">
        </td>
        <td style="width: 85%; text-align: center; border: none;">
            <h2 style="margin: 0; font-family: serif; text-transform: uppercase;">PEMERINTAH PROVINSI KALIMANTAN SELATAN
            </h2>
            <h1 style="margin: 5px 0; font-family: sans-serif;">SMAN 3 BANJARMASIN</h1>
            <p style="margin: 0; font-size: 12px; font-style: italic;">Jl. Ahmad Yani Km. 6, Banjarmasin, Kalimantan
                Selatan</p>
        </td>
    </tr>
</table>

<div style="text-align: center; margin-bottom: 20px;">
    <h3 style="margin: 0; text-transform: uppercase; text-decoration: underline;">{{ $judul_laporan }}</h3>
    @if (isset($periode))
        <p style="margin: 5px 0; font-size: 12px;">{{ $periode }}</p>
    @endif
</div>
