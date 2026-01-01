<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('no_bukti')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('faktur_penjualan_id')->index();
            $table->decimal('nominal', 20, 2);
            $table->string('metode_pembayaran', 50); // transfer, tunai, cek, dll
            $table->text('keterangan')->nullable();
            $table->string('status', 20)->default('draft'); // draft, approved, dll
            $table->unsignedBigInteger('dibuat_oleh_id')->nullable();
            $table->unsignedBigInteger('disetujui_oleh_id')->nullable();
            $table->timestamp('tanggal_disetujui')->nullable();
            $table->timestamps();

            $table->foreign('faktur_penjualan_id')->references('id')->on('faktur_penjualan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_penjualan');
    }
};
