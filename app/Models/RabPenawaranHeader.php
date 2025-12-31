<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabPenawaranHeader extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'nomor_penawaran',
        'nama_penawaran',
        'tanggal_penawaran',
        'versi',
        'total_penawaran_bruto',
        'discount_percentage',
        'discount_amount',
        'final_total_penawaran',
        'status',
        'area',
        'spesifikasi',
        'keterangan', // <- tambahkan ini
        'approval_doc_path',     // legacy (boleh tetap ada)
        'approval_doc_paths',    // baru → menampung banyak file
        'approved_at',
    ];

    protected $casts = [
        'tanggal_penawaran'       => 'date',
        'total_penawaran_bruto'   => 'decimal:2',
        'discount_percentage'     => 'decimal:2',
        'discount_amount'         => 'decimal:2',
        'final_total_penawaran'   => 'decimal:2',
        'keterangan'              => 'string',
        'approval_doc_paths' => 'array',
        'approved_at'        => 'datetime',
    ];

    // --- Relasi ---
    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function sections()
    {
        // jika punya kolom urutan, bisa ganti ->orderBy('urutan')
        return $this->hasMany(RabPenawaranSection::class)->orderBy('id');
    }

    public function items()
    {
        // hasManyThrough eksplisit agar aman terhadap nama FK:
        // rab_penawaran_sections: rab_penawaran_header_id
        // rab_penawaran_items   : rab_penawaran_section_id
        return $this->hasManyThrough(
            RabPenawaranItem::class,
            RabPenawaranSection::class,
            'rab_penawaran_header_id', // FK di tabel sections yang mengarah ke header
            'rab_penawaran_section_id',// FK di tabel items yang mengarah ke section
            'id',                       // PK di tabel headers (model ini)
            'id'                        // PK di tabel sections
        );
    }

    public function schedules()
    {
        return $this->hasMany(\App\Models\RabSchedule::class, 'penawaran_id');
    }

    public function scheduleDetails()
    {
        return $this->hasMany(\App\Models\RabScheduleDetail::class, 'penawaran_id');
    }

    public function salesOrder()
    {
        return $this->hasOne(\App\Models\SalesOrder::class, 'penawaran_id');
    }
}
