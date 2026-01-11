<?php

// app/Models/BappDetail.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BappDetail extends Model {
  protected $fillable = [
    'bapp_id','rab_detail_id','kode','uraian',
    'bobot_item','prev_pct','delta_pct','now_pct',
    'prev_item_pct','delta_item_pct','now_item_pct',
    'qty','satuan','harga',
    'qty_kontrak','qty_realisasi','nilai_kontrak','nilai_realisasi','nilai_adjustment',
    'is_addendum_item'
  ];

  protected $casts = [
    'bobot_item' => 'decimal:2',
    'prev_pct' => 'decimal:2',
    'delta_pct' => 'decimal:2',
    'now_pct' => 'decimal:2',
    'prev_item_pct' => 'decimal:2',
    'delta_item_pct' => 'decimal:2',
    'now_item_pct' => 'decimal:2',
    'qty' => 'decimal:4',
    'qty_kontrak' => 'decimal:4',
    'qty_realisasi' => 'decimal:4',
    'harga' => 'decimal:2',
    'nilai_kontrak' => 'decimal:2',
    'nilai_realisasi' => 'decimal:2',
    'nilai_adjustment' => 'decimal:2',
    'is_addendum_item' => 'boolean',
  ];

  public function bapp(){ return $this->belongsTo(Bapp::class); }
}
