<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // Tambahkan ini

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $table = 'users'; // Pastikan tabel yang digunakan sudah sesuai
    
    protected $fillable = [
        'username',
        'password',
        'akses_perusahaan',
        'buat_perusahaan',
        'edit_perusahaan',
        'hapus_perusahaan',
        'akses_pemberikerja',
        'buat_pemberikerja',
        'edit_pemberikerja',
        'hapus_pemberikerja',
        'akses_proyek',
        'buat_proyek',
        'edit_proyek',
        'hapus_proyek',
        'akses_supplier',
        'buat_supplier',
        'edit_supplier',
        'hapus_supplier',
        'akses_barang',
        'buat_barang',
        'edit_barang',
        'hapus_barang',
        'akses_coa',
        'buat_coa',
        'edit_coa',
        'hapus_coa',
        'akses_po',
        'buat_po',
        'edit_po',
        'hapus_po',
        'revisi_po',
        'print_po',
        'akses_jurnal',
        'buat_jurnal',
        'edit_jurnal',
        'hapus_jurnal',
        'akses_faktur',
        'buat_faktur',
        'edit_faktur',
        'hapus_faktur',
        'akses_user_manager', 
    ];

    protected $hidden = [
        'password',
    ];

    public function perusahaans()
    {
        return $this->belongsToMany(Perusahaan::class, 'user_perusahaan', 'user_id', 'perusahaan_id');
    }

    public function materialUpdates()
    {
        return $this->hasMany(HsdMaterialHistory::class, 'updated_by');
    }

    
}
