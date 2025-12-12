<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $guarded = ['id']; // Semua kolom boleh diisi kecuali ID

    // Satu Kelas punya banyak Siswa
    public function siswa()
    {
        // Kita filter yang role-nya siswa saja
        return $this->hasMany(User::class, 'kelas_id')->where('role', 'siswa');
    }
}
