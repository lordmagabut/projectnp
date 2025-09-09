<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabProgressDetail extends Model
{
    protected $table = 'rab_progress_detail';

    protected $fillable = [
        'rab_progress_id',
        'rab_detail_id',
        'bobot_minggu_ini',
        'penawaran_id'
    ];

    public function progress()
    {
        return $this->belongsTo(RabProgress::class, 'rab_progress_id');
    }

    public function detail()
    {
        return $this->belongsTo(RabDetail::class, 'rab_detail_id');
    }

    public function progressDetails()
    {
        return $this->hasMany(RabProgressDetail::class, 'rab_detail_id');
    }

}
