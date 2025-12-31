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
        Schema::table('uang_muka_penjualan', function (Blueprint $table) {
            $table->string('payment_status')->default('belum_dibayar')->after('status')->comment('belum_dibayar, dibayar');
            $table->date('tanggal_bayar')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uang_muka_penjualan', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'tanggal_bayar']);
        });
    }
};
