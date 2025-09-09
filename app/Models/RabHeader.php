<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabHeader extends Model
{
    protected $table = 'rab_header';
    protected $guarded = ['id']; // Gunakan guarded untuk melindungi 'id' saja

    protected $fillable = [
        'proyek_id',
        'kategori_id',
        'parent_id', 
        'kode',
        'kode_sort',
        'deskripsi',
        'nilai',
        'bobot',
        'created_at',
        'updated_at',
    ];

    // Relasi ke proyek
    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function rabDetails()
    {
        return $this->hasMany(RabDetail::class, 'rab_header_id');
    }

        // Relasi self-referencing ke parent RabHeader (Induk)
    public function parent()
    {
        return $this->belongsTo(RabHeader::class, 'parent_id');
    }

    // Relasi self-referencing ke children RabHeader (Sub-Induk)
    public function children()
    {
        return $this->hasMany(RabHeader::class, 'parent_id')->orderBy('kode_sort');
    }

    public function schedule()
    {
        return $this->hasOne(\App\Models\RabSchedule::class);
    }

    public function kategori()
    {
        return $this->belongsTo(RabKategori::class, 'kategori_id');
    }

}
