<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Coa extends Model
{
    use NodeTrait;

    // ✅ Tambahkan baris ini:
    protected $table = 'coa';

    protected $fillable = [
        'no_akun',
        'nama_akun',
        'tipe',
        'parent_id',
        'suspended',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('_lft');
    }
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan');
    }
}
