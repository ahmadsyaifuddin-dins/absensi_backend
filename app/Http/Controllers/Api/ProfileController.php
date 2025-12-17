<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    // 1. UPDATE PROFIL (Nama & Foto)
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nama' => 'required|string|max:255',
            'foto_profil' => 'nullable|image|max:2048', // Maks 2MB
        ]);

        $user->nama = $request->nama;

        // Cek apakah ada upload foto baru
        if ($request->hasFile('foto_profil')) {
            // Hapus foto lama jika ada (optional, biar hemat storage)
            if ($user->foto_profil && file_exists(public_path($user->foto_profil))) {
                unlink(public_path($user->foto_profil));
            }

            // Simpan foto baru
            $file = $request->file('foto_profil');
            $namaFile = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('profil'), $namaFile);
            
            $user->foto_profil = 'profil/' . $namaFile;
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'data' => $user
        ]);
    }

    // 2. GANTI PASSWORD
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // Harus ada field new_password_confirmation di kiriman
        ]);

        $user = $request->user();

        // Cek Password Lama
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password lama salah!'
            ], 400);
        }

        // Update Password Baru
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diganti'
        ]);
    }
}