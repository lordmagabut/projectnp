<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Po extends Model
{
    use HasFactory;

    protected $table = 'po';

    protected $fillable = [
        'no_po',
        'validation_key',
        'tanggal',
        'id_supplier',
        'nama_supplier',
        'id_proyek',
        'id_perusahaan',
        'keterangan',
        'total',
        'status',         
        'printed_at',
        'file_path',
        'dibuat_oleh', 'dibuat_at',
        'direview_oleh', 'direview_at',
        'disetujui_oleh', 'disetujui_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'printed_at' => 'datetime',
        'dibuat_at' => 'datetime',
        'direview_at' => 'datetime',
        'disetujui_at' => 'datetime',
    ];

    // Derived attributes
    public function getPpnPersenAttribute()
    {
        // PPN disimpan di PoDetail sebagai nilai global per PO
        // Ambil dari baris pertama jika tersedia; fallback 0
        try {
            $detail = $this->relationLoaded('details') ? $this->details->first() : $this->details()->select('ppn_persen')->first();
            return (float)($detail->ppn_persen ?? 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function getDiskonPersenAttribute()
    {
        // Diskon disimpan di PoDetail sebagai nilai global per PO
        try {
            $detail = $this->relationLoaded('details') ? $this->details->first() : $this->details()->select('diskon_persen')->first();
            return (float)($detail->diskon_persen ?? 0);
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }

    public function details()
    {
        return $this->hasMany(PoDetail::class, 'po_id');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek');
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function poDetails()
    {
        return $this->hasMany(PoDetail::class, 'po_id')->with('barang');
    }

    public function penerimaans()
    {
        return $this->hasMany(PenerimaanPembelian::class, 'po_id');
    }

    public function fakturs()
    {
        return $this->hasMany(Faktur::class, 'id_po');
    }

    // Audit relations
    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
    public function direviewOleh()
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }
    public function disetujuiOleh()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
