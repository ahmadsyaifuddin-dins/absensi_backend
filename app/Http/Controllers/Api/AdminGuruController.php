<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminGuruController extends Controller
{
    // 1. LIHAT SEMUA GURU
    public function index()
    {
        // Ambil user yang role-nya 'guru'
        $guru = User::where('role', 'guru')
                    ->orderBy('nama', 'asc')
                    ->get();

        return response()->json([
            'status' => 'success',
            'data' => $guru
        ]);
    }

    // 2. TAMBAH GURU BARU
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'email' => 'required|email|unique:pengguna,email', // Cek tabel pengguna
            'nisn_nip' => 'required|unique:pengguna,nisn_nip', // NIP harus unik
            'password' => 'required|min:6',
        ]);

        $guru = User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'nisn_nip' => $request->nisn_nip, // Simpan NIP
            'password' => Hash::make($request->password), // Enkripsi password
            'role' => 'guru', // Otomatis set role jadi guru
            'foto_profil' => null, // Default kosong
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru berhasil ditambahkan',
            'data' => $guru
        ]);
    }

    // 3. UPDATE DATA GURU
    public function update(Request $request, $id)
    {
        $guru = User::find($id);

        if (!$guru || $guru->role != 'guru') {
            return response()->json(['message' => 'Guru tidak ditemukan'], 404);
        }

        $request->validate([
            'nama' => 'required',
            // Validasi unik kecuali punya diri sendiri
            'email' => 'required|email|unique:pengguna,email,' . $id, 
            'nisn_nip' => 'required|unique:pengguna,nisn_nip,' . $id,
        ]);

        // Data yang mau diupdate
        $dataUpdate = [
            'nama' => $request->nama,
            'email' => $request->email,
            'nisn_nip' => $request->nisn_nip,
        ];

        // Cek apakah password mau diganti?
        if ($request->filled('password')) {
            $dataUpdate['password'] = Hash::make($request->password);
        }

        $guru->update($dataUpdate);

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru berhasil diperbarui',
            'data' => $guru
        ]);
    }

    // 4. HAPUS GURU
    public function destroy($id)
    {
        $guru = User::find($id);

        if (!$guru || $guru->role != 'guru') {
            return response()->json(['message' => 'Guru tidak ditemukan'], 404);
        }

        $guru->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru berhasil dihapus'
        ]);
    }
}