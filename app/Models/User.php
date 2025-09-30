<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $table = 'users';

    /**
     * Kolom yang boleh di-mass assign.
     * Sesuaikan dengan struktur tabelmu.
     * - name & email dipakai di Blade/Controller baru.
     * - username tetap didukung bila kolomnya masih ada (disinkronkan dari name).
     */
    protected $fillable = [
        'name',
        'email',
        'password',

        // ——— PERMISSION FLAGS KHUSUS APP-KAMU ———
        'akses_perusahaan', 'buat_perusahaan', 'edit_perusahaan', 'hapus_perusahaan',
        'akses_pemberikerja', 'buat_pemberikerja', 'edit_pemberikerja', 'hapus_pemberikerja',
        'akses_proyek', 'buat_proyek', 'edit_proyek', 'hapus_proyek',
        'akses_supplier', 'buat_supplier', 'edit_supplier', 'hapus_supplier',
        'akses_barang', 'buat_barang', 'edit_barang', 'hapus_barang',
        'akses_coa', 'buat_coa', 'edit_coa', 'hapus_coa',
        'akses_po', 'buat_po', 'edit_po', 'hapus_po', 'revisi_po', 'print_po',
        'akses_jurnal', 'buat_jurnal', 'edit_jurnal', 'hapus_jurnal',
        'akses_faktur', 'buat_faktur', 'edit_faktur', 'hapus_faktur',
        'akses_user_manager',
        // (opsional) tetap izinkan mass-assign username bila kolomnya ada:
        'username',
    ];

    /**
     * Sembunyikan saat serialisasi.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data.
     * Semua flag akses dibikin boolean agar rapi saat dipakai di Blade/logic.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',

        'akses_perusahaan' => 'bool', 'buat_perusahaan' => 'bool', 'edit_perusahaan' => 'bool', 'hapus_perusahaan' => 'bool',
        'akses_pemberikerja' => 'bool', 'buat_pemberikerja' => 'bool', 'edit_pemberikerja' => 'bool', 'hapus_pemberikerja' => 'bool',
        'akses_proyek' => 'bool', 'buat_proyek' => 'bool', 'edit_proyek' => 'bool', 'hapus_proyek' => 'bool',
        'akses_supplier' => 'bool', 'buat_supplier' => 'bool', 'edit_supplier' => 'bool', 'hapus_supplier' => 'bool',
        'akses_barang' => 'bool', 'buat_barang' => 'bool', 'edit_barang' => 'bool', 'hapus_barang' => 'bool',
        'akses_coa' => 'bool', 'buat_coa' => 'bool', 'edit_coa' => 'bool', 'hapus_coa' => 'bool',
        'akses_po' => 'bool', 'buat_po' => 'bool', 'edit_po' => 'bool', 'hapus_po' => 'bool', 'revisi_po' => 'bool', 'print_po' => 'bool',
        'akses_jurnal' => 'bool', 'buat_jurnal' => 'bool', 'edit_jurnal' => 'bool', 'hapus_jurnal' => 'bool',
        'akses_faktur' => 'bool', 'buat_faktur' => 'bool', 'edit_faktur' => 'bool', 'hapus_faktur' => 'bool',
        'akses_user_manager' => 'bool',
    ];

    /**
     * Relasi ke perusahaan via pivot user_perusahaan.
     */
    public function perusahaans()
    {
        return $this->belongsToMany(Perusahaan::class, 'user_perusahaan', 'user_id', 'perusahaan_id');
    }

    /**
     * Contoh relasi histori material (sesuaikan nama model & FK jika berbeda).
     */
    public function materialUpdates()
    {
        return $this->hasMany(HsdMaterialHistory::class, 'updated_by');
    }

    /**
     * Sinkronisasi otomatis: bila kolom `username` ada di tabel,
     * setiap kali `name` di-set, kita isi juga `username` agar tetap kompatibel
     * dengan bagian sistem lama yang mungkin masih membaca `username`.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;

        // Jika kolom "username" tersedia di DB, sinkronkan.
        if ($this->hasUsernameColumn()) {
            $this->attributes['username'] = $value;
        }
    }

    /**
     * Helper untuk mendeteksi keberadaan kolom username tanpa bikin query berat.
     * Cache statis per request agar tidak cek berulang.
     */
    protected function hasUsernameColumn(): bool
    {
        static $has = null;
        if ($has !== null) return $has;

        try {
            // gunakan schema builder via koneksi model ini
            $schema = $this->getConnection()->getSchemaBuilder();
            $has = $schema->hasColumn($this->getTable(), 'username');
        } catch (\Throwable $e) {
            $has = false;
        }
        return $has;
    }
}
