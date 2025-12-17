<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\Request;

class IzinController extends Controller
{
    // 1. LIST IZIN (Untuk dilihat Guru)
    public function index()
    {
        // Ambil semua data yg statusnya Izin/Sakit
        // Urutkan dari yang terbaru
        $listIzin = Absensi::with('user')
            ->whereIn('status', ['Izin', 'Sakit'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'List Pengajuan Izin',
            'data' => $listIzin
        ]);
    }

    // 2. UPDATE STATUS (Terima/Tolak)
    public function update(Request $request, $id)
    {
        $request->validate([
            'validasi' => 'required|in:Diterima,Ditolak'
        ]);

        $absen = Absensi::find($id);

        if (!$absen) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $absen->update([
            'validasi' => $request->validasi
        ]);

        return response()->json([
            'message' => 'Status berhasil diubah menjadi ' . $request->validasi,
            'data' => $absen
        ]);
    }
}