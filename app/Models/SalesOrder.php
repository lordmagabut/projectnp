<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'proyek_id', 'penawaran_id', 'no_so', 'tanggal', 'total', 'created_by'
    ];

    public function lines()
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function proyek()
    {
        return $this->belongsTo(Proyek::class);
    }

    public function penawaran()
    {
        return $this->belongsTo(RabPenawaranHeader::class, 'penawaran_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
