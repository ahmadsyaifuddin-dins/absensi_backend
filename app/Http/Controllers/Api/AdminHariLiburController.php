<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;

class AdminHariLiburController extends Controller
{
    // List Hari Libur (Urutkan dari yang terbaru)
    public function index()
    {
        $data = HariLibur::orderBy('tanggal', 'desc')->get();
        return response()->json(['data' => $data]);
    }

    // Tambah Hari Libur
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal',
            'keterangan' => 'required|string',
        ]);

        $libur = HariLibur::create([
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json(['message' => 'Hari libur berhasil ditambahkan', 'data' => $libur]);
    }

    // Hapus Hari Libur (Kalau ternyata jadi masuk sekolah)
    public function destroy($id)
    {
        $libur = HariLibur::find($id);
        if ($libur) {
            $libur->delete();
            return response()->json(['message' => 'Hari libur dihapus']);
        }
        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
}