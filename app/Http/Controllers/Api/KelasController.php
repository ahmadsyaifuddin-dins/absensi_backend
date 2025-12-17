<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    // 1. LIST KELAS + JUMLAH SISWA
    public function index()
    {
        // withCount('siswa') -> Otomatis hitung jumlah data di relasi 'siswa'
        $kelas = Kelas::withCount('siswa')->orderBy('nama_kelas', 'asc')->get();

        return response()->json([
            'message' => 'Data kelas berhasil diambil',
            'data' => $kelas
        ]);
    }

    // 2. TAMBAH KELAS
    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|unique:kelas,nama_kelas'
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => $request->nama_kelas,
            // Slug opsional, buat gaya-gayaan aja
            'slug' => \Illuminate\Support\Str::slug($request->nama_kelas)
        ]);

        return response()->json(['message' => 'Kelas berhasil dibuat', 'data' => $kelas]);
    }

    // 3. EDIT KELAS
    public function update(Request $request, $id)
    {
        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $request->validate([
            'nama_kelas' => 'required|unique:kelas,nama_kelas,'.$id // Ignore unique buat diri sendiri
        ]);

        $kelas->update([
            'nama_kelas' => $request->nama_kelas,
            'slug' => \Illuminate\Support\Str::slug($request->nama_kelas)
        ]);

        return response()->json(['message' => 'Kelas berhasil diupdate']);
    }

    // 4. HAPUS KELAS
    public function destroy($id)
    {
        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        // Opsional: Cek dulu ada siswanya gak? Kalau ada jangan dihapus (bahaya)
        if ($kelas->siswa()->count() > 0) {
            return response()->json(['message' => 'Gagal! Kelas masih memiliki siswa.'], 400);
        }

        $kelas->delete();
        return response()->json(['message' => 'Kelas berhasil dihapus']);
    }
}