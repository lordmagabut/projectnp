<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanPenjualanDetail extends Model
{
    protected $table = 'penerimaan_penjualan_details';

    protected $fillable = [
        'penerimaan_penjualan_id',
        'faktur_penjualan_id',
        'nominal',
        'pph_dipotong',
        'keterangan_pph',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'pph_dipotong' => 'decimal:2',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanPenjualan::class, 'penerimaan_penjualan_id');
    }

    public function faktur()
    {
        return $this->belongsTo(FakturPenjualan::class, 'faktur_penjualan_id');
    }
}
