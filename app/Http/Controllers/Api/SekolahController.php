<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSekolah;
use Illuminate\Http\Request;

class SekolahController extends Controller
{
    // 1. GET SETTINGAN
    public function index()
    {
        $sekolah = PengaturanSekolah::first();
        return response()->json([
            'message' => 'Data Pengaturan Sekolah',
            'data' => $sekolah
        ]);
    }

    // 2. UPDATE SETTINGAN
    public function update(Request $request)
    {
        $request->validate([
            'nama_sekolah' => 'required|string',
            'jam_masuk' => 'required', // Format H:i:s / H:i
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_meter' => 'required|numeric',
        ]);

        $sekolah = PengaturanSekolah::first();
        if (!$sekolah) {
            $sekolah = new PengaturanSekolah();
        }

        $sekolah->update([
            'nama_sekolah' => $request->nama_sekolah,
            'jam_masuk' => $request->jam_masuk,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meter' => $request->radius_meter,
        ]);

        return response()->json([
            'message' => 'Pengaturan berhasil disimpan!',
            'data' => $sekolah
        ]);
    }
}