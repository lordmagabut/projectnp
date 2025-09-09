<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabPenawaranItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rab_penawaran_section_id',
        'rab_detail_id',
        'kode',
        'deskripsi',
        'volume',
        'satuan',
        'harga_satuan_dasar',
        'harga_satuan_calculated', 
        'harga_satuan_penawaran',
        'total_penawaran_item',
        'harga_material_dasar_item',
        'harga_upah_dasar_item',
        'harga_material_calculated_item', 
        'harga_upah_calculated_item',     
        'harga_material_penawaran_item',  
        'harga_upah_penawaran_item',      
        'area',
        'spesifikasi',
    ];

    public function section()
    {
        return $this->belongsTo(RabPenawaranSection::class, 'rab_penawaran_section_id');
    }

    public function rabDetail()
    {
        return $this->belongsTo(RabDetail::class, 'rab_detail_id');
    }
}
