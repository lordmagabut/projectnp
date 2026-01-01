<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faktur_penjualan', function (Blueprint $table) {
            $table->id();
            $table->string('no_faktur')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('sertifikat_pembayaran_id')->index();
            $table->unsignedBigInteger('id_proyek')->nullable();
            $table->unsignedBigInteger('id_perusahaan')->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);      // DPP
            $table->decimal('total_diskon', 20, 2)->default(0);
            $table->decimal('total_ppn', 20, 2)->default(0);
            $table->decimal('total', 20, 2)->default(0);
            $table->decimal('uang_muka_dipakai', 20, 2)->default(0);
            $table->string('status', 20)->default('draft');      // draft, approved, etc
            $table->string('status_pembayaran', 20)->default('belum_dibayar'); // belum_dibayar, sebagian, lunas
            $table->timestamps();

            $table->foreign('sertifikat_pembayaran_id')->references('id')->on('sertifikat_pembayaran')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faktur_penjualan');
    }
};
