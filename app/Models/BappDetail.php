<?php

// app/Models/BappDetail.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BappDetail extends Model {
  protected $fillable = [
    'bapp_id','rab_detail_id','kode','uraian',
    'bobot_item','prev_pct','delta_pct','now_pct',
    'prev_item_pct','delta_item_pct','now_item_pct'
  ];
  public function bapp(){ return $this->belongsTo(Bapp::class); }
}
