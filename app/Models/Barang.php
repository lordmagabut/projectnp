<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'tipe_id',
        'coa_persediaan_id',
        'coa_beban_id',
        'coa_hpp_id'
    ];

    public function tipe()
    {
        return $this->belongsTo(TipeBarangJasa::class, 'tipe_id');
    }
    public function coaHpp()
    {
        return $this->belongsTo(Coa::class, 'coa_hpp_id');
    }

    public function coaBeban()
    {
        return $this->belongsTo(Coa::class, 'coa_beban_id');
    }

}
