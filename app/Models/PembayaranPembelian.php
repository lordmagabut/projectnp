<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPembelian extends Model
{
    protected $table = 'pembayaran_pembelian';
    protected $guarded = [];

    public function faktur()
    {
        return $this->belongsTo(Faktur::class, 'faktur_id');
    }

    public function coa()
    {
        return $this->belongsTo(Coa::class, 'coa_id');
    }
}