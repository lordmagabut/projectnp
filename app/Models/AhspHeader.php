<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AhspHeader extends Model
{
    protected $table = 'ahsp_header';
    protected $fillable = [
        'kode_pekerjaan', 'nama_pekerjaan', 'satuan',
        'kategori_id', 'total_harga', 'is_locked',
        'total_harga_pembulatan' // <<< TAMBAHKAN INI
    ];

    public function kategori()
    {
        return $this->belongsTo(AhspKategori::class, 'kategori_id');
    }

    public function details()
    {
        return $this->hasMany(AhspDetail::class, 'ahsp_id');
    }

    public function getTotalMaterialAttribute()
    {
        return $this->details->where('tipe', 'material')->sum(function($d) {
            return $d->subtotal_final ?? $d->subtotal;
        });
    }

    public function getTotalUpahAttribute()
    {
        return $this->details->where('tipe', 'upah')->sum(function($d) {
            return $d->subtotal_final ?? $d->subtotal;
        });
    }

    /**
     * Generate kode otomatis dengan format AHSP-nnnnn
     * Contoh: AHSP-00001, AHSP-00002, dst
     */
    public static function generateKode()
    {
        $lastRecord = self::orderByDesc('id')->first();
        $lastNumber = 0;

        if ($lastRecord && $lastRecord->kode_pekerjaan) {
            // Ekstrak angka dari kode terakhir (format: AHSP-nnnnn)
            $matches = [];
            if (preg_match('/AHSP-(\d+)/', $lastRecord->kode_pekerjaan, $matches)) {
                $lastNumber = intval($matches[1]);
            }
        }

        $newNumber = $lastNumber + 1;
        return sprintf('AHSP-%05d', $newNumber);
    }
}
