<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HsdUpahHistory extends Model
{
    use HasFactory;

    protected $table = 'hsd_upah_history';

    protected $fillable = [
        'hsd_upah_id',
        'harga_satuan',     // lama
        'harga_baru',       // baru
        'tanggal_berlaku',
        'sumber',
        'updated_by',
    ];

    public function upah()
    {
        return $this->belongsTo(HsdUpah::class, 'hsd_upah_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
