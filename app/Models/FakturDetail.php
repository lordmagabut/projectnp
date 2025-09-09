<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakturDetail extends Model
{
    protected $table = 'faktur_detail';

    protected $fillable = [
        'id_faktur',
        'kode_item',
        'po_detail_id',
        'uraian',
        'qty',
        'uom',
        'harga',
        'diskon_persen',
        'diskon_rupiah',
        'ppn_persen',
        'ppn_rupiah',
        'total',
        'coa_beban_id',
        'coa_persediaan_id',
        'coa_hpp_id',
    ];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'id_faktur');
    }

    public function po_detail()
{
    return $this->belongsTo(\App\Models\PoDetail::class, 'po_detail_id');
}

}
