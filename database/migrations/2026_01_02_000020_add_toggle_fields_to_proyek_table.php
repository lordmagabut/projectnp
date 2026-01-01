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
            $table->boolean('gunakan_uang_muka')->default(false)->after('persen_dp')->comment('Toggle uang muka digunakan atau tidak');
            $table->boolean('gunakan_retensi')->default(false)->after('durasi_retensi')->comment('Toggle retensi digunakan atau tidak');
            $table->enum('pph_dipungut', ['ya', 'tidak'])->default('ya')->after('uang_muka_mode')->comment('PPh dipungut dari tagihan atau dibayar sendiri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            $table->dropColumn(['gunakan_uang_muka', 'gunakan_retensi', 'pph_dipungut']);
        });
    }
};
