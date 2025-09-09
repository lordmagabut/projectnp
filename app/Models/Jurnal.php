<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    protected $table = 'jurnal';

    protected $fillable = [
        'no_jurnal',
        'id_perusahaan',
        'tanggal',
        'keterangan',
        'tipe',
        'total',
        'ref_id',
        'ref_table'
    ];

    public function details()
    {
        return $this->hasMany(JurnalDetail::class, 'jurnal_id');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }
    public static function generateNomor()
    {
        $tanggal = now()->format('ymd'); // YYMMDD
        $prefix = 'JV-' . $tanggal;

        // Ambil nomor terakhir dengan prefix yang sama
        $last = self::where('no_jurnal', 'like', $prefix . '%')
            ->orderBy('no_jurnal', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->no_jurnal, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
    public function jurnalDetails()
    {
        return $this->hasMany(\App\Models\JurnalDetail::class, 'jurnal_id');
    }

}
