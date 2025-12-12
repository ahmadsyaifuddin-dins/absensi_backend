<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Pastikan ini ada buat API

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // 1. Definisikan Nama Tabel (Wajib karena beda nama)
    protected $table = 'pengguna';

    // 2. Sesuaikan Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'nama',        // Dulu 'name'
        'email',
        'password',
        'role',
        'nisn_nip',
        'kelas_id',
        'foto_profil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- RELASI (Relationships) ---

    // User (Siswa) punya 1 Kelas
    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    // User punya banyak riwayat Absensi
    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'pengguna_id');
    }
}
