<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use HasFactory;
    
    protected $table = 'hari_libur';
    protected $fillable = ['tanggal', 'keterangan'];
    
    // Casting agar output JSON tanggalnya rapi (tanpa jam)
    protected $casts = [
        'tanggal' => 'date:Y-m-d',
    ];
}