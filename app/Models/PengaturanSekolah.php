<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanSekolah extends Model
{
    use HasFactory;
    
    protected $table = 'pengaturan_sekolah';
    
    protected $fillable = [
        'nama_sekolah',
        'latitude',
        'longitude',
        'radius_meter',
        'jam_masuk',
    ];
}