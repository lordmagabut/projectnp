<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bapp extends Model {
  protected $fillable = [
    'proyek_id','penawaran_id','progress_id','minggu_ke','tanggal_bapp','nomor_bapp','status',
    'total_prev_pct','total_delta_pct','total_now_pct','file_pdf_path','created_by','approved_by','approved_at','notes','sign_by',
    'is_final_account','nilai_kontrak_total','nilai_realisasi_total','nilai_adjustment','final_account_notes'
  ];

  protected $casts = [
    'total_prev_pct' => 'decimal:2',
    'total_delta_pct' => 'decimal:2',
    'total_now_pct' => 'decimal:2',
    'is_final_account' => 'boolean',
    'nilai_kontrak_total' => 'decimal:2',
    'nilai_realisasi_total' => 'decimal:2',
    'nilai_adjustment' => 'decimal:2',
  ];

  public function details(){ return $this->hasMany(BappDetail::class); }
  public function proyek(){ return $this->belongsTo(Proyek::class); }
  public function penawaran(){ return $this->belongsTo(RabPenawaranHeader::class,'penawaran_id'); }
  public function progress(){ return $this->belongsTo(RabProgress::class,'progress_id'); }
  public function sertifikatPembayaran(){ return $this->hasMany(SertifikatPembayaran::class, 'bapp_id'); }
}
