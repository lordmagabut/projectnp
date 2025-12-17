<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
public function up(): void
{
    Schema::create('pembayaran_pembelian', function (Blueprint $table) {
        $table->id();
        $table->string('no_pembayaran')->unique(); 
        $table->date('tanggal');

        // PERBAIKAN DI SINI:
        // Sesuai DDL Anda, faktur.id adalah bigint(20) SIGNED
        $table->bigInteger('faktur_id'); 
        $table->foreign('faktur_id')->references('id')->on('faktur')->onDelete('cascade');

        // coa.id dan perusahaan.id adalah UNSIGNED
        $table->bigInteger('id_perusahaan')->unsigned();
        $table->foreign('id_perusahaan')->references('id')->on('perusahaan');

        $table->bigInteger('coa_id')->unsigned(); 
        $table->foreign('coa_id')->references('id')->on('coa');

        $table->decimal('nominal', 15, 2);
        $table->string('keterangan')->nullable();
        $table->timestamps();
    });
}

    /**
     * Batalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran_pembelian');
    }
};