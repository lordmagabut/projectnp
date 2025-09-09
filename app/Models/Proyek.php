<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyek extends Model
{
    use HasFactory;

    protected $table = 'proyek';

    protected $fillable = [
        'nama_proyek',
        'pemberi_kerja_id',
        'no_spk',
        'nilai_spk',
        'nilai_penawaran',
        'diskon_rab',
        'nilai_kontrak',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'lokasi',
        'file_spk',
        'jenis_proyek'     
    ];

    public function pemberiKerja()
    {
        return $this->belongsTo(PemberiKerja::class);
    }

    public function rabHeaders()
    {
        return $this->hasMany(RabHeader::class, 'proyek_id');
    }

    public function rabDetails()
    {
        return $this->hasMany(RabDetail::class, 'proyek_id');
    }

    public function penawarans()
    {
        return $this->hasMany(RabPenawaranHeader::class);
    }

}
