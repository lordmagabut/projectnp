<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikatPembayaran extends Model
{
    protected $table = 'sertifikat_pembayaran';

    protected $fillable = [
        'bapp_id','nomor','tanggal','termin_ke','persen_progress',
        'nilai_wo_material','nilai_wo_jasa','nilai_wo_total',
        'uang_muka_persen','uang_muka_nilai','pemotongan_um_persen','pemotongan_um_nilai','sisa_uang_muka',
        'nilai_progress_rp','retensi_persen','retensi_nilai',
        'total_dibayar','ppn_persen','ppn_nilai','total_tagihan','terbilang',
        'pemberi_tugas_nama','pemberi_tugas_perusahaan','pemberi_tugas_jabatan',
        'penerima_tugas_nama','penerima_tugas_perusahaan','penerima_tugas_jabatan',
        'dibuat_oleh_id','disetujui_oleh_id',
    ];

    public function bapp() {
        return $this->belongsTo(\App\Models\Bapp::class,'bapp_id');
    }
}
