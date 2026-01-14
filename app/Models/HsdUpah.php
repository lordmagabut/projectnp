<?php

// app/Models/HsdUpah.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HsdUpah extends Model
{
    protected $table = 'hsd_upah';
    protected $fillable = ['kode', 'jenis_pekerja', 'satuan', 'harga_satuan', 'keterangan'];

    public function histories()
    {
        return $this->hasMany(HsdUpahHistory::class, 'hsd_upah_id');
    }

    /**
     * Generate kode otomatis dengan format U-nnnnn
     * Contoh: U-00001, U-00002, dst
     */
    public static function generateKode()
    {
        $lastRecord = self::orderByDesc('id')->first();
        $lastNumber = 0;

        if ($lastRecord && $lastRecord->kode) {
            // Ekstrak angka dari kode terakhir (format: U-nnnnn)
            $matches = [];
            if (preg_match('/U-(\d+)/', $lastRecord->kode, $matches)) {
                $lastNumber = intval($matches[1]);
            }
        }

        $newNumber = $lastNumber + 1;
        return sprintf('U-%05d', $newNumber);
    }

}
