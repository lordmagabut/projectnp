<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabHeader extends Model
{
    protected $table = 'rab_header';

    // Amankan id saja, izinkan kolom lain via $fillable
    protected $guarded = ['id'];

    protected $fillable = [
        'proyek_id',
        'kategori_id',
        'parent_id',
        'kode',
        'kode_sort',
        'deskripsi',
        'nilai_material',    // agregat material
        'nilai_upah',        // agregat upah
        'nilai',             // agregat gabungan
        'bobot',
    ];

    protected $casts = [
        'nilai_material' => 'decimal:2',
        'nilai_upah'     => 'decimal:2',
        'nilai'          => 'decimal:2',
        'bobot'          => 'decimal:4',
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

    // Self-referencing: parent (induk)
    public function parent()
    {
        return $this->belongsTo(RabHeader::class, 'parent_id');
    }

    // Self-referencing: children (sub-induk)
    public function children()
    {
        return $this->hasMany(RabHeader::class, 'parent_id')->orderBy('kode_sort');
    }

    public function schedule()
    {
        return $this->hasOne(RabSchedule::class);
    }

    public function kategori()
    {
        return $this->belongsTo(RabKategori::class, 'kategori_id');
    }
}
