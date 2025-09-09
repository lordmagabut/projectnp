<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RabProgressDetail extends Model
{
    use HasFactory;

    protected $table = 'rab_progress_detail';
    public $timestamps = true;

    // Columns we actually write
    protected $fillable = [
        'rab_progress_id',
        'rab_detail_id',
        'bobot_minggu_ini',     // delta % proyek minggu ini
        'pct_item_minggu_ini',  // delta % item minggu ini
        'bobot_item_snapshot',  // snapshot bobot item saat submit
    ];

    protected $casts = [
        'bobot_minggu_ini'     => 'decimal:4',
        'bobot_item_snapshot'  => 'decimal:4',
        'pct_item_minggu_ini'  => 'decimal:2',
    ];

    /** Header progress (memuat proyek_id, penawaran_id, minggu_ke, status, dst) */
    public function progress()
    {
        return $this->belongsTo(RabProgress::class, 'rab_progress_id');
    }

    /** Master item RAB */
    public function detail()
    {
        return $this->belongsTo(RabDetail::class, 'rab_detail_id');
    }

    /* ---------- Query helpers (optional, tapi enak dipakai) ---------- */

    /** Filter by proyek melalui header progress */
    public function scopeOfProyek($q, int $proyekId)
    {
        return $q->whereHas('progress', fn($p) => $p->where('proyek_id', $proyekId));
    }

    /** Filter by penawaran (nullable) melalui header progress */
    public function scopeOfPenawaran($q, ?int $penawaranId)
    {
        if ($penawaranId) {
            $q->whereHas('progress', fn($p) => $p->where('penawaran_id', $penawaranId));
        }
        return $q;
    }

    /** Hanya yang FINAL (melalui header) */
    public function scopeFinal($q)
    {
        return $q->whereHas('progress', fn($p) => $p->where('status', 'final'));
    }

    /** Minggu tertentu / sebelum minggu tertentu (melalui header) */
    public function scopeWeek($q, int $week)
    {
        return $q->whereHas('progress', fn($p) => $p->where('minggu_ke', $week));
    }

    public function scopeBeforeWeek($q, int $week)
    {
        return $q->whereHas('progress', fn($p) => $p->where('minggu_ke', '<', $week));
    }
}
