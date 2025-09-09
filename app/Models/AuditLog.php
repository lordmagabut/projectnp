<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'aksi',
        'tabel',
        'id_tabel',
        'deskripsi',
        'created_at'
    ];
}
