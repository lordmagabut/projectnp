<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    protected $table = 'retur_pembelian';
    
    protected $fillable = [
        'no_retur',
        'tanggal',
        'penerimaan_id',
        'id_supplier',
        'nama_supplier',
        'id_proyek',
        'id_perusahaan',
        'alasan',
        'status',
        'jurnal_id',
    ];

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanPembelian::class, 'penerimaan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }

    public function details()
    {
        return $this->hasMany(ReturPembelianDetail::class, 'retur_id');
    }

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }
}
