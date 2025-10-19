<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikatPembayaran extends Model
{
    protected $table = 'sertifikat_pembayaran';

    protected $fillable = [
        'bapp_id','tanggal','termin_ke',
        'persen_progress','persen_progress_prev','persen_progress_delta',

        'nilai_wo_material','nilai_wo_jasa','nilai_wo_total',

        'uang_muka_persen','uang_muka_nilai',
        'pemotongan_um_persen','pemotongan_um_nilai','sisa_uang_muka',

        'retensi_persen','retensi_nilai',
        'nilai_progress_rp','total_dibayar',

        'ppn_persen','ppn_nilai','total_tagihan',

        'dpp_material','dpp_jasa',

        // info pihak terkait
        'pemberi_tugas_nama','pemberi_tugas_jabatan','pemberi_tugas_perusahaan',
        'penerima_tugas_nama','penerima_tugas_jabatan','penerima_tugas_perusahaan',

        // identitas dokumen
        'nomor','terbilang','dibuat_oleh_id',

        // opsional: dipakai di view PDF (aman sekalipun kolomnya belum ada)
        'po_wo_spk_no','po_wo_spk_tanggal',
    ];

    protected $casts = [
        'tanggal'            => 'date',
        'po_wo_spk_tanggal'  => 'date',

        // persen pakai 4 desimal agar akurat untuk delta kecil
        'persen_progress'        => 'decimal:4',
        'persen_progress_prev'   => 'decimal:4',
        'persen_progress_delta'  => 'decimal:4',

        // rupiah 2 desimal
        'nilai_wo_material'  => 'decimal:2',
        'nilai_wo_jasa'      => 'decimal:2',
        'nilai_wo_total'     => 'decimal:2',

        'uang_muka_persen'   => 'decimal:4',
        'uang_muka_nilai'    => 'decimal:2',
        'pemotongan_um_persen'=> 'decimal:4',
        'pemotongan_um_nilai'=> 'decimal:2',
        'sisa_uang_muka'     => 'decimal:2',

        'retensi_persen'     => 'decimal:4',
        'retensi_nilai'      => 'decimal:2',

        'nilai_progress_rp'  => 'decimal:2',
        'total_dibayar'      => 'decimal:2',

        'ppn_persen'         => 'decimal:4',
        'ppn_nilai'          => 'decimal:2',
        'total_tagihan'      => 'decimal:2',

        'dpp_material'       => 'decimal:2',
        'dpp_jasa'           => 'decimal:2',
    ];

    protected $attributes = [
        // default supaya tidak null
        'persen_progress_prev'   => 0,
        'persen_progress_delta'  => 0,
    ];

    /* ======================= RELATION ======================= */
    public function bapp()
    {
        return $this->belongsTo(\App\Models\Bapp::class);
    }

    /* ======================= SCOPES ========================= */
    public function scopeForProyek($q, $proyekId)
    {
        return $q->whereHas('bapp', fn($qq) => $qq->where('proyek_id', $proyekId));
    }

    public function scopeSameVendor($q, $vendorName)
    {
        return $q->where('penerima_tugas_perusahaan', $vendorName);
    }

    public function scopeBeforeDate($q, $date)
    {
        return $q->whereDate('tanggal', '<', $date);
    }

    /* ======================= HELPERS ======================== */
    /**
     * Ambil SP sebelumnya untuk konteks proyek & vendor yang sama.
     */
    public function previousForContext(): ?self
    {
        $proyekId   = optional($this->bapp)->proyek_id;
        $vendorName = $this->penerima_tugas_perusahaan;

        if (!$proyekId) return null;

        return static::forProyek($proyekId)
            ->when($vendorName, fn($q) => $q->sameVendor($vendorName))
            ->where('id', '!=', $this->id)
            ->beforeDate($this->tanggal ?? now())
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /* ======================= ACCESSORS ====================== */
    /**
     * Auto-hitunɡ total WO bila null di DB.
     */
    public function getNilaiWoTotalAttribute($value)
    {
        if ($value === null) {
            $mat = (float)($this->attributes['nilai_wo_material'] ?? 0);
            $jas = (float)($this->attributes['nilai_wo_jasa'] ?? 0);
            return number_format($mat + $jas, 2, '.', '');
        }
        return $value;
    }

    /**
     * Delta progress otomatis bila tidak diisi (kumulatif sekarang - kumulatif sebelumnya).
     */
    public function getPersenProgressDeltaAttribute($value)
    {
        if ($value !== null) return $value;

        $prev = $this->persen_progress_prev;
        if ($prev === null) {
            $prev = optional($this->previousForContext())->persen_progress ?? 0;
        }

        $now   = (float)($this->attributes['persen_progress'] ?? 0);
        $delta = max(0, round($now - (float)$prev, 4));

        // kembalikan sebagai string decimal agar konsisten dengan cast
        return number_format($delta, 4, '.', '');
    }

    /* ======================= HOOKS ========================== */
    protected static function booted()
    {
        static::saving(function (self $m) {
            // isi nilai_wo_total jika kosong
            if (is_null($m->nilai_wo_total)) {
                $m->nilai_wo_total = round((float)$m->nilai_wo_material + (float)$m->nilai_wo_jasa, 2);
            }

            // backfill prev & delta jika belum diisi
            if (is_null($m->persen_progress_prev)) {
                $m->persen_progress_prev = optional($m->previousForContext())->persen_progress ?? 0;
            }
            if (is_null($m->persen_progress_delta)) {
                $m->persen_progress_delta = max(
                    0,
                    round((float)$m->persen_progress - (float)$m->persen_progress_prev, 4)
                );
            }
        });
    }
}
