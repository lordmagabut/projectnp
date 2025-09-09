<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabSchedule extends Model
{
    protected $table = 'rab_schedule';

    protected $fillable = [
        'proyek_id',
        'penawaran_id',
        'rab_header_id',          // leaf header (WBS 1.1.1 dst)
        'rab_penawaran_item_id',  // item penawaran (unit pekerjaan)
        'minggu_ke',
        'durasi',
        'start_date', 'end_date', // aktifkan jika kolom ini memang ada di tabel
    ];

    protected $casts = [
        'proyek_id'            => 'integer',
        'penawaran_id'         => 'integer',
        'rab_header_id'        => 'integer',
        'rab_penawaran_item_id'=> 'integer',
        'minggu_ke'            => 'integer',
        'durasi'               => 'integer',
        'start_date'            => 'date',
        'end_date'              => 'date',
    ];

    /* ================= Relations ================= */

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    // Leaf header WBS (mis. 1.1.1)
    public function header()
    {
        return $this->belongsTo(RabHeader::class, 'rab_header_id');
    }

    public function penawaran()
    {
        return $this->belongsTo(RabPenawaranHeader::class, 'penawaran_id');
    }

    public function item()
    {
        return $this->belongsTo(RabPenawaranItem::class, 'rab_penawaran_item_id');
    }

    /**
     * Meta tanggal (start/end/total_weeks) per (proyek_id, penawaran_id).
     * Cocokkan juga proyek_id via whereColumn.
     */
    public function meta()
    {
        return $this->hasOne(RabScheduleMeta::class, 'penawaran_id', 'penawaran_id')
            ->whereColumn('rab_schedule_meta.proyek_id', 'rab_schedule.proyek_id');
    }

    /* ================= Scopes ================= */

    public function scopeForPenawaran($q, int $proyekId, int $penawaranId)
    {
        return $q->where('proyek_id', $proyekId)
                 ->where('penawaran_id', $penawaranId);
    }

    public function scopeLeafOnly($q)
    {
        // baris yang punya rab_penawaran_item_id (per item)
        return $q->whereNotNull('rab_penawaran_item_id');
    }

    public function penawaranItem() {
        return $this->belongsTo(\App\Models\RabPenawaranItem::class, 'rab_penawaran_item_id');
    }

    public function rabDetail() {
        return $this->belongsTo(\App\Models\RabDetail::class, 'rab_detail_id');
    }

    public function rabHeader(){ return $this->belongsTo(RabHeader::class, 'rab_header_id'); }
}
