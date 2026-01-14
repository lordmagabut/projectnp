<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class HsdMaterial extends Model
{
    protected $table = 'hsd_material';
    protected $fillable = ['kode', 'nama', 'satuan', 'harga_satuan', 'keterangan'];

    public function histories()
    {
        return $this->hasMany(HsdMaterialHistory::class, 'hsd_material_id');
    }

    /**
     * Generate kode otomatis dengan format M-nnnnn
     * Contoh: M-00001, M-00002, dst
     */
    public static function generateKode()
    {
        $lastRecord = self::orderByDesc('id')->first();
        $lastNumber = 0;

        if ($lastRecord && $lastRecord->kode) {
            // Ekstrak angka dari kode terakhir (format: M-nnnnn)
            $matches = [];
            if (preg_match('/M-(\d+)/', $lastRecord->kode, $matches)) {
                $lastNumber = intval($matches[1]);
            }
        }

        $newNumber = $lastNumber + 1;
        return sprintf('M-%05d', $newNumber);
    }

}
