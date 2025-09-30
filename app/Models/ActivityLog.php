<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','event','description','ip_address','device_name','user_agent',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
