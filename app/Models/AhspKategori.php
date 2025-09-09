<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AhspKategori extends Model
{
    protected $table = 'ahsp_kategori';
    protected $fillable = ['nama'];

    public function ahsps()
    {
        return $this->hasMany(AhspHeader::class, 'kategori_id');
    }
}
