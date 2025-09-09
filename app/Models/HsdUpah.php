<?php

// app/Models/HsdUpah.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HsdUpah extends Model
{
    protected $table = 'hsd_upah';
    protected $fillable = ['kode', 'jenis_pekerja', 'satuan', 'harga_satuan', 'keterangan'];

    public function histories()
    {
        return $this->hasMany(HsdUpahHistory::class, 'hsd_upah_id');
    }

}
