<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SiswaController extends Controller
{
    // 1. LIST SEMUA SISWA
    public function index()
    {
        // Ambil user yg role-nya siswa, serta data kelasnya
        $siswa = User::where('role', 'siswa')
                     ->with('kelas') // Load relasi kelas
                     ->orderBy('nama', 'asc')
                     ->get();

        return response()->json(['data' => $siswa]);
    }

    // 2. LIST KELAS (Buat Dropdown Pilihan)
    public function getKelas()
    {
        $kelas = Kelas::all();
        return response()->json(['data' => $kelas]);
    }

    // 3. TAMBAH SISWA BARU
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'nisn_nip' => 'required|unique:pengguna,nisn_nip', // Cek biar NISN gak kembar
            'kelas_id' => 'required',
            'password' => 'nullable|min:6'
        ]);

        $user = User::create([
            'nama' => $request->nama,
            'nisn_nip' => $request->nisn_nip,
            'email' => $request->nisn_nip . '@gmail.com', // Email dummy otomatis
            'role' => 'siswa',
            'kelas_id' => $request->kelas_id,
            // Kalau password kosong, default: 123456
            'password' => Hash::make($request->password ?? '123456'),
        ]);

        return response()->json(['message' => 'Siswa berhasil ditambahkan', 'data' => $user]);
    }

    // 4. UPDATE SISWA
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if(!$user) return response()->json(['message' => 'Siswa tidak ditemukan'], 404);

        $user->update([
            'nama' => $request->nama,
            'nisn_nip' => $request->nisn_nip,
            'kelas_id' => $request->kelas_id,
        ]);
        
        // Update password cuma kalau diisi
        if($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return response()->json(['message' => 'Data siswa diperbarui']);
    }

    // 5. HAPUS SISWA
    public function destroy($id)
    {
        $user = User::find($id);
        if($user) {
            $user->delete();
            return response()->json(['message' => 'Siswa dihapus']);
        }
        return response()->json(['message' => 'Gagal hapus'], 400);
    }
}