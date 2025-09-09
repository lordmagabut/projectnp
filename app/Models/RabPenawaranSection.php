<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabPenawaranSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'rab_penawaran_header_id',
        'rab_header_id',
        'profit_percentage',
        'overhead_percentage',
        'total_section_penawaran',
        'parent_id',
    ];

    public function header()
    {
        return $this->belongsTo(RabPenawaranHeader::class, 'rab_penawaran_header_id');
    }

    public function rabHeader()
    {
        return $this->belongsTo(RabHeader::class, 'rab_header_id');
    }

    public function items()
    {
        return $this->hasMany(RabPenawaranItem::class);
    }

    public function children()
    {
        return $this->hasMany(RabPenawaranSection::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(RabPenawaranSection::class, 'parent_id');
    }

}