<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTask extends Model
{
    use HasFactory;

    protected $table = 'project_tasks';

    protected $fillable = [
        'proyek_id',
        'kode',
        'parent_id',
        'deskripsi',
        'bobot',
        'tanggal_mulai',
        'durasi',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            $task->kode_sort = self::generateKodeSort($task->kode);
        });

        static::updating(function ($task) {
            $task->kode_sort = self::generateKodeSort($task->kode);
        });
    }

    public static function generateKodeSort($kode)
    {
        $parts = explode('.', $kode);
        $kodeSort = '';

        foreach ($parts as $part) {
            $kodeSort .= str_pad($part, 3, '0', STR_PAD_LEFT);
        }

        return $kodeSort;
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class, 'proyek_id');
    }

    // Relasi ke rencana mingguan
    public function rencanaMingguan()
    {
        return $this->hasMany(TaskWeeklyPlan::class, 'task_id');
    }

    // Relasi ke realisasi mingguan
    public function realisasiMingguan()
    {
        return $this->hasMany(TaskWeeklyActual::class, 'task_id');
    }

    public function parent()
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectTask::class, 'parent_id');
    }
    public function weeklyPlans()
    {
        return $this->hasMany(TaskWeeklyPlan::class, 'task_id');
    }

    public function getMingguKeAttribute()
    {
        if ($this->tanggal_mulai && $this->proyek && $this->proyek->tanggal_mulai) {
            return \Carbon\Carbon::parse($this->proyek->tanggal_mulai)->diffInWeeks(\Carbon\Carbon::parse($this->tanggal_mulai)) + 1;
        }
        return null;
    }


}
