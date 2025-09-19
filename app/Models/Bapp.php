<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bapp extends Model {
  protected $fillable = [
    'proyek_id','penawaran_id','progress_id','minggu_ke','tanggal_bapp','nomor_bapp','status',
    'total_prev_pct','total_delta_pct','total_now_pct','file_pdf_path','created_by','approved_by','approved_at','notes'
  ];
  public function details(){ return $this->hasMany(BappDetail::class); }
  public function proyek(){ return $this->belongsTo(Proyek::class); }
  public function penawaran(){ return $this->belongsTo(RabPenawaranHeader::class,'penawaran_id'); }
  public function progress(){ return $this->belongsTo(RabProgress::class,'progress_id'); }
}
