<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    protected $guarded = ['id'];

    // Casting tipe data biar otomatis jadi Tipe yang pas
    protected $casts = [
        'terlambat' => 'boolean', // Jadi true/false
        'menit_keterlambatan' => 'integer',
        // 'tanggal' => 'date', // Jadi format tanggal Carbon
        'tanggal' => 'date:Y-m-d',
    ];

    // Relasi balik ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
