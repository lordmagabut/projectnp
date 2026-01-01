<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabPenawaranWeight extends Model
{
    use HasFactory;

    protected $table = 'rab_penawaran_weight';

    protected $fillable = [
        'proyek_id',
        'penawaran_id',
        'rab_header_id',
        'rab_penawaran_section_id',
        'rab_penawaran_item_id',
        'level',
        'gross_value',
        'weight_pct_project',
        'weight_pct_in_header',
        'computed_at',
    ];

    protected $dates = [
        'computed_at',
        'created_at',
        'updated_at',
    ];

    public function penawaran()
    {
        return $this->belongsTo(RabPenawaranHeader::class, 'penawaran_id');
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function rabHeader()
    {
        return $this->belongsTo(RabHeader::class, 'rab_header_id');
    }

    public function penawaranSection()
    {
        return $this->belongsTo(RabPenawaranSection::class, 'rab_penawaran_section_id');
    }

    public function penawaranItem()
    {
        return $this->belongsTo(RabPenawaranItem::class, 'rab_penawaran_item_id');
    }
}
