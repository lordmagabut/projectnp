<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianDetail extends Model
{
    protected $table = 'penerimaan_pembelian_detail';
    
    protected $fillable = [
        'penerimaan_id',
        'po_detail_id',
        'kode_item',
        'uraian',
        'qty_po',
        'qty_diterima',
        'qty_terfaktur',
        'uom',
        'keterangan',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanPembelian::class, 'penerimaan_id');
    }

    public function poDetail()
    {
        return $this->belongsTo(PoDetail::class, 'po_detail_id');
    }

    public function returDetails()
    {
        return $this->hasMany(ReturPembelianDetail::class, 'penerimaan_detail_id');
    }
}
