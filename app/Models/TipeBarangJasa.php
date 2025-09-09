<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeBarangJasa extends Model
{
    use HasFactory;

    protected $table = 'tipe_barang_jasa';

    protected $fillable = ['tipe'];
}
