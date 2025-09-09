<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabScheduleMeta extends Model
{
    protected $table = 'rab_schedule_meta';
    protected $fillable = ['proyek_id','penawaran_id','start_date','end_date','total_weeks'];

    public function proyek()    { return $this->belongsTo(Proyek::class); }
    public function penawaran() { return $this->belongsTo(RabPenawaranHeader::class, 'penawaran_id'); }
}
