<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            if (!Schema::hasColumn('sertifikat_pembayaran', 'uang_muka_penjualan_id')) {
                $table->unsignedBigInteger('uang_muka_penjualan_id')->nullable()->index()->after('bapp_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            if (Schema::hasColumn('sertifikat_pembayaran', 'uang_muka_penjualan_id')) {
                $table->dropColumn('uang_muka_penjualan_id');
            }
        });
    }
};
