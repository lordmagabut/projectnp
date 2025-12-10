<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            // Menambahkan kolom string untuk path file
            // nullable() artinya boleh kosong
            // after('file_spk') agar posisi kolomnya rapi setelah file_spk
            $table->string('file_gambar_kerja')->nullable()->after('file_spk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback
            $table->dropColumn('file_gambar_kerja');
        });
    }
};