<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HsdMaterialHistory extends Model
{
    use HasFactory;

    protected $table = 'hsd_material_history';

    protected $fillable = [
        'hsd_material_id',
        'harga_satuan',      // harga lama
        'harga_baru',        // harga setelah update
        'tanggal_berlaku',
        'sumber',
        'updated_by',
    ];
    

    /**
     * Relasi ke material utama
     */
    public function material()
    {
        return $this->belongsTo(HsdMaterial::class, 'hsd_material_id');
    }

    /**
     * Relasi ke user yang melakukan update
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
