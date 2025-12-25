<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedBigInteger('coa_id');
            $table->decimal('saldo_awal', 20, 2)->default(0)->comment('Saldo awal akun (debit positif, kredit negatif)');
            $table->date('tanggal')->comment('Tanggal saldo awal');
            $table->string('keterangan')->nullable();
            $table->unsignedBigInteger('jurnal_id')->nullable()->comment('Reference ke jurnal otomatis');
            $table->timestamps();

            $table->foreign('id_perusahaan')->references('id')->on('perusahaan')->onDelete('cascade');
            $table->foreign('coa_id')->references('id')->on('coa')->onDelete('cascade');
            $table->foreign('jurnal_id')->references('id')->on('jurnal')->onDelete('set null');
            
            $table->unique(['id_perusahaan', 'coa_id', 'tanggal'], 'unique_opening_balance_per_coa_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opening_balances');
    }
};
