<?php

// app/Models/ProyekTaxProfile.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProyekTaxProfile extends Model
{
    protected $fillable = [
        'proyek_id',
        'is_taxable','ppn_mode','ppn_rate',
        'apply_pph','pph_rate','pph_base',
        'rounding','extra_options',
        'aktif','effective_from','effective_to',
        'created_by','updated_by'
    ];

    protected $casts = [
        'is_taxable'   => 'boolean',
        'apply_pph'    => 'boolean',
        'ppn_rate'     => 'decimal:3',
        'pph_rate'     => 'decimal:3',
        'extra_options'=> 'array',
        'aktif'        => 'boolean',
        'effective_from'=> 'date',
        'effective_to'  => 'date',
    ];

    public function proyek(){ return $this->belongsTo(Proyek::class); }
}
