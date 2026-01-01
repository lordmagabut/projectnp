<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FakturPenjualan extends Model
{
    protected $table = 'faktur_penjualan';

    protected $fillable = [
        'no_faktur',
        'tanggal',
        'sertifikat_pembayaran_id',
        'id_proyek',
        'id_perusahaan',
        'subtotal',
        'total_diskon',
        'total_ppn',
        'total',
        'uang_muka_dipakai',
        'retensi_persen',
        'retensi_nilai',
        'ppn_persen',
        'ppn_nilai',
        'pph_persen',
        'pph_nilai',
        'status',
        'status_pembayaran',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'subtotal' => 'decimal:2',
        'total_diskon' => 'decimal:2',
        'total_ppn' => 'decimal:2',
        'total' => 'decimal:2',
        'uang_muka_dipakai' => 'decimal:2',
        'retensi_persen' => 'decimal:4',
        'retensi_nilai' => 'decimal:2',
        'ppn_persen' => 'decimal:4',
        'ppn_nilai' => 'decimal:2',
        'pph_persen' => 'decimal:4',
        'pph_nilai' => 'decimal:2',
    ];

    public function sertifikatPembayaran()
    {
        return $this->belongsTo(SertifikatPembayaran::class, 'sertifikat_pembayaran_id');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'id_proyek');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }

    public function penerimaanPenjualan()
    {
        return $this->hasMany(PenerimaanPenjualan::class, 'faktur_penjualan_id');
    }

    public function penerimaanPenjualanDetails()
    {
        return $this->hasMany(PenerimaanPenjualanDetail::class, 'faktur_penjualan_id');
    }

    public static function generateNomorFaktur()
    {
        $tanggal = now()->format('ymd'); // YYMMDD
        $prefix = 'FP-' . $tanggal;

        // Ambil nomor terakhir dengan prefix yang sama
        $last = self::where('no_faktur', 'like', $prefix . '%')
            ->orderBy('no_faktur', 'desc')
            ->first();

        if ($last) {
            $lastNumber = intval(substr($last->no_faktur, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
