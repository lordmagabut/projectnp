<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabDetail extends Model
{
    use HasFactory;

    protected $table = 'rab_detail';
    protected $fillable = [
        'proyek_id',
        'rab_header_id',
        'ahsp_id', // <<< Tambahkan ini
        'kode',
        'kode_sort',
        'deskripsi',
        'area',
        'spesifikasi',
        'satuan',
        'volume',
        'harga_satuan',
        'total',
        'bobot',
        'created_at', // Tambahkan jika belum
        'updated_at', // Tambahkan jika belum
    ];

    // Relasi ke AHSPHeader
    public function ahsp()
    {
        return $this->belongsTo(AhspHeader::class, 'ahsp_id');
    }

    // Relasi ke header (sub-induk)
    public function header()
    {
        return $this->belongsTo(RabHeader::class, 'rab_header_id');
    }

    public function rabHeader()
    {
        return $this->belongsTo(\App\Models\RabHeader::class, 'rab_header_id');
    }

    // Relasi ke proyek
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }
    
    public function penawaranItems()
    {
        return $this->hasMany(RabPenawaranItem::class, 'rab_detail_id');
    }
}
