<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faktur_penjualan', function (Blueprint $table) {
            $table->decimal('retensi_persen', 10, 4)->default(0)->after('uang_muka_dipakai');
            $table->decimal('retensi_nilai', 20, 2)->default(0)->after('retensi_persen');
            $table->decimal('ppn_persen', 10, 4)->default(0)->after('retensi_nilai');
            $table->decimal('ppn_nilai', 20, 2)->default(0)->after('ppn_persen');
        });
    }

    public function down(): void
    {
        Schema::table('faktur_penjualan', function (Blueprint $table) {
            $table->dropColumn(['retensi_persen', 'retensi_nilai', 'ppn_persen', 'ppn_nilai']);
        });
    }
};
