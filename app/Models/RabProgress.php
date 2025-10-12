<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabProgress extends Model
{
    protected $table = 'rab_progress';

    protected $fillable = [
        'proyek_id',
        'minggu_ke',
        'tanggal',
        'user_id',
        'status',
        'penawaran_id'
    ];

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(RabProgressDetail::class, 'rab_progress_id');
    }

    public function progressDetails()
    {
        return $this->hasMany(RabProgressDetail::class, 'rab_detail_id');
    }

    public function penawaran() 
    {
        return $this->belongsTo(\App\Models\RabPenawaranHeader::class, 'penawaran_id');
    }
    
    // Relasi revisi
    public function revisiDari()
    {
        // anak menyimpan revisi_dari_id yang menunjuk ke "this"
        return $this->belongsTo(self::class, 'revisi_dari_id');
    }

    public function revisiKe()
    {
        // cari satu child yang punya revisi_dari_id = this->id
        return $this->hasOne(self::class, 'revisi_dari_id', 'id');
    }

    public function getVersiTerbaru(): self
    {
        $latest = $this->loadMissing('revisiKe')->revisiKe ?: $this;
        while ($latest->relationLoaded('revisiKe') ? $latest->revisiKe : $latest->load('revisiKe')->revisiKe) {
            $latest = $latest->revisiKe;
        }
        return $latest;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'    => 'Draft',
            'final'    => 'Final',
            'approved' => 'Disetujui',
            'revised'  => 'Direvisi',
            default    => ucfirst($this->status ?? '-'),
        };
    }
}
