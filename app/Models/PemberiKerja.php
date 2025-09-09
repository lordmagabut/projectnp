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
        'no_kontak',
        'alamat',
    ];
}
