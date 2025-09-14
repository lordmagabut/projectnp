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
        'ahsp_id',
        'sumber_harga',              // enum: import|manual|ahsp
        'kode',
        'kode_sort',
        'deskripsi',
        'area',
        'spesifikasi',
        'satuan',
        'volume',
        'harga_material',
        'harga_upah',
        'harga_satuan_manual',       // override jika ada negosiasi
        'harga_satuan',              // gabungan (material + upah) atau manual
        'total_material',
        'total_upah',
        'total',                     // gabungan
        'bobot',
        // NOTE: created_at/updated_at tak perlu di-fillable
    ];

    protected $casts = [
        'volume'            => 'decimal:3',
        'harga_material'    => 'decimal:2',
        'harga_upah'        => 'decimal:2',
        'harga_satuan'      => 'decimal:2',
        'harga_satuan_manual'=> 'decimal:2',
        'total_material'    => 'decimal:2',
        'total_upah'        => 'decimal:2',
        'total'             => 'decimal:2',
        'bobot'             => 'decimal:4',
        // 'tgl_harga'       => 'date',   // aktifkan jika nanti ditambah kolom ini
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

    // Relasi ke proyek
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function penawaranItems()
    {
        return $this->hasMany(RabPenawaranItem::class, 'rab_detail_id');
    }

    // ===== Helper / Accessor =====

    // Harga satuan yang dipakai untuk penghitungan gabungan
    public function getHargaTerpakaiAttribute()
    {
        return $this->harga_satuan_manual ?? $this->harga_satuan ?? 0;
    }

    // Nilai total yang dipakai (gabungan)
    public function getNilaiTerpakaiAttribute()
    {
        $vol = $this->volume ?? 0;
        $hs  = $this->harga_satuan_manual ?? $this->harga_satuan ?? 0;
        return (float) $hs * (float) $vol;
    }
}
