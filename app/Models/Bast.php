<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bast extends Model
{
    use HasFactory;

    protected $table = 'basts';

    protected $fillable = [
        'proyek_id',
        'sertifikat_pembayaran_id',
        'parent_bast_id',
        'nomor',
        'jenis_bast',
        'status',
        'tanggal_bast',
        'tanggal_jatuh_tempo_retensi',
        'durasi_retensi_hari',
        'persen_retensi',
        'nilai_retensi',
        'notifikasi_h14_sent',
        'file_bast_pdf',
        'ketentuan',
    ];

    protected $casts = [
        'tanggal_bast' => 'date',
        'tanggal_jatuh_tempo_retensi' => 'date',
        'persen_retensi' => 'decimal:2',
        'nilai_retensi' => 'decimal:2',
        'notifikasi_h14_sent' => 'boolean',
        'ketentuan' => 'array',
    ];

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function sertifikatPembayaran()
    {
        return $this->belongsTo(SertifikatPembayaran::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_bast_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_bast_id');
    }

    /**
     * Boot model events
     */
    protected static function booted()
    {
        // Ketika BAST 1 dihapus, hapus juga BAST 2 (child)
        static::deleting(function (self $bast) {
            if ($bast->jenis_bast === 'bast_1') {
                // Hapus BAST 2 yang merupakan child dari BAST 1 ini
                static::where('parent_bast_id', $bast->id)->delete();
            }
        });
    }
}
