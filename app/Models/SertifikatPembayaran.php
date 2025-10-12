<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SertifikatPembayaran extends Model
{
    protected $table = 'sertifikat_pembayaran';

    protected $fillable = [
        'bapp_id','tanggal','termin_ke','persen_progress',
        'nilai_wo_material','nilai_wo_jasa','nilai_wo_total',
        'uang_muka_persen','uang_muka_nilai','pemotongan_um_persen','pemotongan_um_nilai','sisa_uang_muka',
        'retensi_persen','retensi_nilai',
        'nilai_progress_rp','total_dibayar',
        'ppn_persen','ppn_nilai','total_tagihan',
        'dpp_material','dpp_jasa',              // <-- WAJIB: supaya tidak ter-*strip*
        'pemberi_tugas_nama','pemberi_tugas_jabatan','pemberi_tugas_perusahaan',
        'penerima_tugas_nama','penerima_tugas_jabatan','penerima_tugas_perusahaan',
        'nomor','terbilang','dibuat_oleh_id'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'persen_progress' => 'float',
        'nilai_wo_material' => 'float',
        'nilai_wo_jasa'     => 'float',
        'nilai_wo_total'    => 'float',
        'uang_muka_persen'  => 'float',
        'uang_muka_nilai'   => 'float',
        'pemotongan_um_persen' => 'float',
        'pemotongan_um_nilai'  => 'float',
        'sisa_uang_muka'    => 'float',
        'retensi_persen'    => 'float',
        'retensi_nilai'     => 'float',
        'nilai_progress_rp' => 'float',
        'total_dibayar'     => 'float',
        'ppn_persen'        => 'float',
        'ppn_nilai'         => 'float',
        'total_tagihan'     => 'float',
        'dpp_material'      => 'float',
        'dpp_jasa'          => 'float',
    ];

    public function bapp(){ return $this->belongsTo(\App\Models\Bapp::class); }
}
