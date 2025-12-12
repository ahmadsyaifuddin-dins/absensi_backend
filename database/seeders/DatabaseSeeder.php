<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\PengaturanSekolah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Setup Data Sekolah (SMAN 3 Banjarmasin)
        // Lokasi: Jl. Veteran No.100, Banjarmasin (Koordinat perkiraan SMAN 3)
        PengaturanSekolah::create([
            'latitude_kantor' => -3.326880, // Contoh koordinat SMAN 3
            'longitude_kantor' => 114.591000,
            'radius_km' => 0.05, // 50 Meter
            'jam_masuk' => '07:30:00',
            'jam_pulang' => '15:00:00',
        ]);

        // 2. Setup Data Kelas
        $kelas1 = Kelas::create(['nama_kelas' => 'XII MIPA 1', 'slug' => 'xii-mipa-1']);
        $kelas2 = Kelas::create(['nama_kelas' => 'XII IPS 1', 'slug' => 'xii-ips-1']);
        Kelas::create(['nama_kelas' => 'XI MIPA 1', 'slug' => 'xi-mipa-1']);

        // 3. Setup Akun Admin (Role: Admin)
        User::create([
            'nama' => 'Administrator',
            'email' => 'admin@sekolah.com',
            'password' => Hash::make('password'), // Password default
            'role' => 'admin',
            'nisn_nip' => null,
            'kelas_id' => null,
        ]);

        // 4. Setup Akun Guru (Role: Guru)
        User::create([
            'nama' => 'Pak Budi Guru',
            'email' => 'guru@sekolah.com',
            'password' => Hash::make('password'),
            'role' => 'guru',
            'nisn_nip' => '198001012005011001',
            'kelas_id' => null,
        ]);

        // 5. Setup Akun Siswa (Role: Siswa) - Si Udin
        User::create([
            'nama' => 'Udin Sedunia',
            'email' => 'udin@siswa.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'nisn_nip' => '1234567890',
            'kelas_id' => $kelas1->id, // Masuk XII MIPA 1
        ]);

        // 6. Setup Akun Siswa Lain (Buat rame-rame)
        User::create([
            'nama' => 'Siti Teladan',
            'email' => 'siti@siswa.com',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'nisn_nip' => '0987654321',
            'kelas_id' => $kelas2->id, // Masuk XII IPS 1
        ]);
    }
}
