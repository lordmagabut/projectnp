<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UangMukaPenjualan extends Model
{
    protected $table = 'uang_muka_penjualan';

    protected $fillable = [
        'sales_order_id',
        'proyek_id',
        'nomor_bukti',
        'tanggal',
        'nominal',
        'nominal_digunakan',
        'metode_pembayaran',
        'keterangan',
        'status',
        'payment_status',
        'tanggal_bayar',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_bayar' => 'date',
        'nominal' => 'decimal:2',
        'nominal_digunakan' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSisaUangMuka()
    {
        return max(0, round((float)$this->nominal - (float)$this->nominal_digunakan, 2));
    }

    public function updateNominalDigunakan($amount)
    {
        $this->nominal_digunakan = max(0, (float)$this->nominal_digunakan + $amount);
        if ($this->nominal_digunakan >= $this->nominal) {
            $this->status = 'lunas';
        } elseif ($this->nominal_digunakan > 0) {
            $this->status = 'sebagian';
        } else {
            $this->status = 'diterima';
        }
        $this->save();
    }
}
