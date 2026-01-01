<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proyek extends Model
{
    use HasFactory;

    protected $table = 'proyek';

    protected $fillable = [
        'nama_proyek',
        'pemberi_kerja_id',
        'no_spk',
        'nilai_spk',
        'nilai_penawaran',
        'diskon_rab',
        'nilai_kontrak',
        'tanggal_mulai',
        'tanggal_selesai',
        'persen_dp',
        'gunakan_uang_muka',
        'persen_retensi',
        'durasi_retensi',
        'gunakan_retensi',
        'status',
        'lokasi',
        'file_spk',
        'file_gambar_kerja',
        'jenis_proyek',
        'penawaran_price_mode',
        'uang_muka_mode',
        'pph_dipungut'
    ];

    public function pemberiKerja()
    {
        return $this->belongsTo(PemberiKerja::class);
    }

    public function rabHeaders()
    {
        return $this->hasMany(RabHeader::class, 'proyek_id');
    }

    public function rabDetails()
    {
        return $this->hasMany(RabDetail::class, 'proyek_id');
    }

    public function penawarans()
    {
        return $this->hasMany(RabPenawaranHeader::class);
    }
    
       public function taxProfiles()
    {
        return $this->hasMany(\App\Models\ProyekTaxProfile::class, 'proyek_id');
    }

    public function taxProfileAktif()
    {
        // hanya 1 baris aktif per proyek (sesuai constraint)
        return $this->hasOne(\App\Models\ProyekTaxProfile::class, 'proyek_id')
                    ->where('aktif', 1);
                    // ->withDefault(); // opsional: biar nggak null di Blade
    }

}
