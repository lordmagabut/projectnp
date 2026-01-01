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
        Schema::table('faktur_penjualan', function (Blueprint $table) {
            $table->decimal('pph_persen', 10, 4)->default(0)->after('ppn_nilai');
            $table->decimal('pph_nilai', 20, 2)->default(0)->after('pph_persen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faktur_penjualan', function (Blueprint $table) {
            $table->dropColumn(['pph_persen', 'pph_nilai']);
        });
    }
};
