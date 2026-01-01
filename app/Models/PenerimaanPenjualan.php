<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanPenjualan extends Model
{
    protected $table = 'penerimaan_penjualan';

    protected $fillable = [
        'no_bukti',
        'tanggal',
        'faktur_penjualan_id',
        'nominal',
        'pph_dipotong',
        'keterangan_pph',
        'metode_pembayaran',
        'keterangan',
        'status',
        'dibuat_oleh_id',
        'disetujui_oleh_id',
        'tanggal_disetujui',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_disetujui' => 'datetime',
        'nominal' => 'decimal:2',
        'pph_dipotong' => 'decimal:2',
    ];

    public function fakturPenjualan()
    {
        return $this->belongsTo(FakturPenjualan::class, 'faktur_penjualan_id');
    }

    public function pembuatnya()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    public function penyetujunya()
    {
        return $this->belongsTo(User::class, 'disetujui_oleh_id');
    }

    public static function generateNomorBukti()
    {
        $tanggal = now()->format('ymd'); // YYMMDD
        $prefix = 'PN-' . $tanggal;

        $last = self::where('no_bukti', 'like', $prefix . '%')
            ->orderBy('no_bukti', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->no_bukti, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
