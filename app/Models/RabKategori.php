<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabKategori extends Model
{
    protected $table = 'rab_kategori';
    protected $fillable = ['nama_kategori', 'kode'];

    public function rabHeaders()
    {
        return $this->hasMany(RabHeader::class, 'kategori_id');
    }
}
