<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelianDetail extends Model
{
    protected $table = 'retur_pembelian_detail';
    
    protected $fillable = [
        'retur_id',
        'penerimaan_detail_id',
        'kode_item',
        'uraian',
        'qty_retur',
        'uom',
        'harga',
        'total',
        'alasan',
    ];

    public function retur()
    {
        return $this->belongsTo(ReturPembelian::class, 'retur_id');
    }

    public function penerimaanDetail()
    {
        return $this->belongsTo(PenerimaanPembelianDetail::class, 'penerimaan_detail_id');
    }
}
