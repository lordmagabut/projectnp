<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RabPenawaranHeader extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyek_id',
        'nama_penawaran',
        'tanggal_penawaran',
        'versi',
        'total_penawaran_bruto',
        'discount_percentage',
        'discount_amount',
        'final_total_penawaran',
        'status',
        'area',
        'spesifikasi',
    ];

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function sections()
    {
        return $this->hasMany(RabPenawaranSection::class);
    }
    
        public function items()
    {
        return $this->hasManyThrough(RabPenawaranItem::class, RabPenawaranSection::class);
    }

    public function schedules()
    { 
        return $this->hasMany(\App\Models\RabSchedule::class, 'penawaran_id'); 
    }

    public function scheduleDetails()
    { 
        return $this->hasMany(\App\Models\RabScheduleDetail::class, 'penawaran_id'); 
    }
    
}