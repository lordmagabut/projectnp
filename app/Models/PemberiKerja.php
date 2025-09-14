<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemberiKerja extends Model
{
    use HasFactory;

    protected $table = 'pemberi_kerja';

    protected $fillable = [
        'nama_pemberi_kerja',
        'pic',
        'jabatan_pic',
        'nama_direktur',
        'jabatan_direktur',
        'no_kontak',
        'alamat',
    ];
}
