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
        Schema::table('uang_muka_pembelian', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['transfer', 'cek', 'tunai', 'giro'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uang_muka_pembelian', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['transfer', 'cek', 'tunai', 'giro'])->nullable(false)->default('transfer')->change();
        });
    }
};
