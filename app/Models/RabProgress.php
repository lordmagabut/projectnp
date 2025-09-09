<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabProgress extends Model
{
    protected $table = 'rab_progress';

    protected $fillable = [
        'proyek_id',
        'minggu_ke',
        'tanggal',
        'user_id',
        'status',
        'penawaran_id'
    ];

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function details()
    {
        return $this->hasMany(RabProgressDetail::class, 'rab_progress_id');
    }

    public function progressDetails()
    {
        return $this->hasMany(RabProgressDetail::class, 'rab_detail_id');
    }

    public function penawaran() 
    {
        return $this->belongsTo(\App\Models\RabPenawaranHeader::class, 'penawaran_id');
    }
}
