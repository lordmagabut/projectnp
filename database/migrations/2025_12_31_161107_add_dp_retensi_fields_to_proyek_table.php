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
            $table->decimal('persen_dp', 5, 2)->default(0)->after('tanggal_selesai')->comment('Persentase Down Payment');
            $table->decimal('persen_retensi', 5, 2)->default(0)->after('persen_dp')->comment('Persentase Retensi');
            $table->integer('durasi_retensi')->default(0)->after('persen_retensi')->comment('Durasi Retensi dalam hari');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            $table->dropColumn(['persen_dp', 'persen_retensi', 'durasi_retensi']);
        });
    }
};
