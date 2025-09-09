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
        'tanggal',
        'id_supplier',
        'nama_supplier',
        'id_proyek',
        'id_perusahaan',
        'keterangan',
        'total',
        'status',         
        'printed_at',
        'file_path'      
    ];

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


}
