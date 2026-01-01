<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_penjualan_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penerimaan_penjualan_id')->index();
            $table->unsignedBigInteger('faktur_penjualan_id')->index();
            $table->decimal('nominal', 20, 2);
            $table->decimal('pph_dipotong', 20, 2)->default(0);
            $table->string('keterangan_pph', 100)->nullable();
            $table->timestamps();

            $table->foreign('penerimaan_penjualan_id')
                ->references('id')->on('penerimaan_penjualan')->onDelete('cascade');
            $table->foreign('faktur_penjualan_id')
                ->references('id')->on('faktur_penjualan')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_penjualan_details');
    }
};
