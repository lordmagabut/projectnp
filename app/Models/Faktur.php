<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faktur extends Model
{
    protected $table = 'faktur';

    protected $fillable = [
        'no_faktur',
        'tanggal',
        'id_po',
        'id_supplier',
        'nama_supplier',
        'id_perusahaan',
        'id_proyek',
        'subtotal',
        'total_diskon',
        'total_ppn',
        'total',
        'total_kredit_retur',
        'sudah_dibayar',
        'status_pembayaran',
        'status',
        'file_path',
        'jurnal_id',
        'uang_muka_dipakai',  // NEW
        'uang_muka_id',       // NEW
    ];

    // Relasi ke detail
    public function details()
    {
        return $this->hasMany(FakturDetail::class, 'id_faktur');
    }

    // Relasi ke perusahaan
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }

    // Relasi ke proyek
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek');
    }

    // Relasi ke PO (jika digunakan)
    public function po()
    {
        return $this->belongsTo(Po::class, 'id_po');
    }

    // Relasi ke supplier (jika diperlukan, meskipun nama sudah disimpan langsung)
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }
    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }
    public function fakturDetails()
        {
            return $this->hasMany(\App\Models\FakturDetail::class, 'id_faktur');
        }
    public function pembayaran()
    {
        // Sesuaikan dengan nama class Model yang kita buat tadi
        return $this->hasMany(PembayaranPembelian::class, 'faktur_id');
    }

    // Relasi ke UangMukaPembelian
    public function uangMuka()
    {
        return $this->belongsTo(UangMukaPembelian::class, 'uang_muka_id');
    }
}
