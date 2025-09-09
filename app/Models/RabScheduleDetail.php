<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabScheduleDetail extends Model
{
    use HasFactory;

    protected $table = 'rab_schedule_detail';

    protected $fillable = [
        'proyek_id',
        'penawaran_id',
        'rab_header_id',           // opsional: bisa null jika per-item
        'rab_penawaran_item_id',   // opsional: null jika per-header
        'minggu_ke',
        'bobot_mingguan',
    ];

    protected $casts = [
        'proyek_id'             => 'integer',
        'penawaran_id'          => 'integer',
        'rab_header_id'         => 'integer',
        'rab_penawaran_item_id' => 'integer',
        'minggu_ke'             => 'integer',
        'bobot_mingguan'        => 'decimal:4',
    ];

    /* ================= Relations ================= */

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function penawaran()
    {
        return $this->belongsTo(RabPenawaranHeader::class, 'penawaran_id');
    }

    public function rabHeader()
    {
        return $this->belongsTo(RabHeader::class, 'rab_header_id');
    }

    public function item()
    {
        return $this->belongsTo(RabPenawaranItem::class, 'rab_penawaran_item_id');
    }

    /* ================= Scopes ================= */

    public function scopeForPenawaran($q, int $proyekId, int $penawaranId)
    {
        return $q->where('proyek_id', $proyekId)
                 ->where('penawaran_id', $penawaranId);
    }

    public function scopeWeekRange($q, int $from, int $to)
    {
        return $q->whereBetween('minggu_ke', [$from, $to]);
    }
}
