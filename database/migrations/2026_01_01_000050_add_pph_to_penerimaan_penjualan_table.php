<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan_penjualan', function (Blueprint $table) {
            $table->decimal('pph_dipotong', 20, 2)->default(0)->after('nominal');
            $table->string('keterangan_pph', 100)->nullable()->after('pph_dipotong');
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_penjualan', function (Blueprint $table) {
            $table->dropColumn(['pph_dipotong', 'keterangan_pph']);
        });
    }
};
