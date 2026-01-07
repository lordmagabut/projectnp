<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelian extends Model
{
    protected $table = 'penerimaan_pembelian';
    
    protected $fillable = [
        'no_penerimaan',
        'tanggal',
        'po_id',
        'id_supplier',
        'nama_supplier',
        'id_proyek',
        'id_perusahaan',
        'dibuat_oleh',
        'dibuat_at',
        'disetujui_oleh',
        'disetujui_at',
        'keterangan',
        'no_surat_jalan',
        'file_surat_jalan',
        'status',
        'status_penagihan',
    ];

    protected $casts = [
        'dibuat_at' => 'datetime',
        'disetujui_at' => 'datetime',
    ];

    public function po()
    {
        return $this->belongsTo(Po::class, 'po_id');
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

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function details()
    {
        return $this->hasMany(PenerimaanPembelianDetail::class, 'penerimaan_id');
    }

    public function returs()
    {
        return $this->hasMany(ReturPembelian::class, 'penerimaan_id');
    }
}
