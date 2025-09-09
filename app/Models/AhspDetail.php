<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AhspDetail extends Model
{
    protected $table = 'ahsp_detail';
    protected $fillable = [
        'ahsp_id', 'tipe', 'referensi_id', 'koefisien',
        'harga_satuan', 'subtotal'
    ];

    public function header()
    {
        return $this->belongsTo(AhspHeader::class, 'ahsp_id');
    }

    // Tidak bisa pakai foreign key langsung, jadi relasi manual di controller
    public function referensi()
    {
        return $this->morphTo(null, 'tipe', 'referensi_id');
    }
}
