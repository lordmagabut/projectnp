<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UangMukaPembelian extends Model
{
    protected $table = 'uang_muka_pembelian';

    protected $fillable = [
        'no_uang_muka',
        'tanggal',
        'po_id',
        'id_supplier',
        'nama_supplier',
        'id_perusahaan',
        'id_proyek',
        'nominal',
        'metode_pembayaran',
        'no_rekening_bank',
        'nama_bank',
        'tanggal_transfer',
        'no_bukti_transfer',
        'keterangan',
        'status',
        'nominal_digunakan',
        'file_path',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_transfer' => 'date',
        'nominal' => 'decimal:2',
        'nominal_digunakan' => 'decimal:2',
    ];

    // Relationships
    public function po()
    {
        return $this->belongsTo(Po::class, 'po_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek');
    }

    public function jurnals()
    {
        return $this->morphMany(Jurnal::class, 'jurnalable');
    }

    // Helper methods
    public function getSisaUangMukaAttribute()
    {
        return max(0, $this->nominal - $this->nominal_digunakan);
    }

    public function updateNominalDigunakan($tambahan)
    {
        $this->nominal_digunakan += $tambahan;
        $this->save();
    }
}
