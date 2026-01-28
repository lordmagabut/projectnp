<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AhspDetail extends Model
{
    protected $table = 'ahsp_detail';
    protected $fillable = [
        'ahsp_id', 'tipe', 'referensi_id', 'koefisien',
        'harga_satuan', 'subtotal', 'diskon_persen', 'ppn_persen',
        'diskon_nominal', 'ppn_nominal', 'subtotal_final'
    ];

    protected $casts = [
        'koefisien' => 'decimal:4',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'diskon_persen' => 'decimal:2',
        'ppn_persen' => 'decimal:2',
        'diskon_nominal' => 'decimal:2',
        'ppn_nominal' => 'decimal:2',
        'subtotal_final' => 'decimal:2',
    ];

    public function header()
    {
        return $this->belongsTo(AhspHeader::class, 'ahsp_id');
    }

    // Tidak bisa pakai foreign key langsung, jadi relasi manual di controller
    public function referensi()
    {
        return $this->morphTo(null, 'tipe', 'referensi_id');
    }

    /**
     * Calculate subtotal after diskon and PPN
     * Rumus: subtotal_awal - (subtotal_awal * diskon%) + ((subtotal_awal - (subtotal_awal * diskon%)) * ppn%)
     */
    public function calculateSubtotal()
    {
        $subtotalAwal = $this->subtotal ?? 0;
        $diskonPersen = $this->diskon_persen ?? 0;
        $ppnPersen = $this->ppn_persen ?? 0;

        // Hitung nominal diskon
        $diskonNominal = $subtotalAwal * ($diskonPersen / 100);
        
        // Hitung subtotal setelah diskon
        $subtotalSetelahDiskon = $subtotalAwal - $diskonNominal;
        
        // Hitung nominal PPN
        $ppnNominal = $subtotalSetelahDiskon * ($ppnPersen / 100);
        
        // Hitung subtotal final
        $subtotalFinal = $subtotalSetelahDiskon + $ppnNominal;

        return [
            'diskon_nominal' => $diskonNominal,
            'ppn_nominal' => $ppnNominal,
            'subtotal_final' => $subtotalFinal,
        ];
    }

    /**
     * Mutator untuk auto-calculate saat save
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $calculation = $model->calculateSubtotal();
            $model->diskon_nominal = $calculation['diskon_nominal'];
            $model->ppn_nominal = $calculation['ppn_nominal'];
            $model->subtotal_final = $calculation['subtotal_final'];
        });
    }
}
