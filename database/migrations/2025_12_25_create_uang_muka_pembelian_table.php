<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing table if migration ran partially
        Schema::dropIfExists('uang_muka_pembelian');
        
        Schema::create('uang_muka_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_uang_muka')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('po_id');
            $table->unsignedBigInteger('id_supplier');
            $table->string('nama_supplier');
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedBigInteger('id_proyek')->nullable();
            $table->decimal('nominal', 20, 2);
            $table->enum('metode_pembayaran', ['transfer', 'cek', 'tunai', 'giro'])->nullable();
            $table->string('no_rekening_bank')->nullable();
            $table->string('nama_bank')->nullable();
            $table->date('tanggal_transfer')->nullable();
            $table->string('no_bukti_transfer')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->decimal('nominal_digunakan', 20, 2)->default(0);
            $table->string('file_path')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('po_id');
            $table->index('id_supplier');
            $table->index('id_perusahaan');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uang_muka_pembelian');
    }
};
