<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('proyek_id')->index();
            $table->unsignedBigInteger('sertifikat_pembayaran_id')->nullable()->index();
            $table->unsignedBigInteger('parent_bast_id')->nullable()->index();

            $table->string('nomor', 100)->unique();
            $table->string('jenis_bast', 20)->index(); // bast_1 atau bast_2
            $table->string('status', 30)->default('draft'); // draft|scheduled|active|completed|cancelled

            $table->date('tanggal_bast')->nullable();
            $table->date('tanggal_jatuh_tempo_retensi')->nullable();
            $table->integer('durasi_retensi_hari')->nullable();
            $table->decimal('persen_retensi', 8, 2)->default(0);
            $table->decimal('nilai_retensi', 15, 2)->nullable();

            $table->boolean('notifikasi_h14_sent')->default(false);
            $table->string('file_bast_pdf')->nullable();

            $table->timestamps();

            // Tidak menambahkan constraint FK keras untuk fleksibilitas restore data
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basts');
    }
};
