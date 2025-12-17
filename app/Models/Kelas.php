<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;
    protected $table = 'kelas';
    protected $guarded = [];

    // RELASI: Satu Kelas punya banyak Siswa (User)
    public function siswa()
    {
        // Pastikan 'kelas_id' sesuai dengan kolom di tabel pengguna
        return $this->hasMany(User::class, 'kelas_id');
    }
}