<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom uang_muka_persen ke sales_orders
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'uang_muka_persen')) {
                $table->decimal('uang_muka_persen', 5, 2)->default(0)->after('total');
            }
        });

        // Buat tabel uang_muka_penjualan (mirror dari uang_muka_pembelian)
        Schema::create('uang_muka_penjualan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('proyek_id')->nullable()->index();
            $table->string('nomor_bukti')->nullable();
            $table->date('tanggal');
            $table->decimal('nominal', 20, 2);
            $table->decimal('nominal_digunakan', 20, 2)->default(0);
            $table->string('metode_pembayaran')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('diterima'); // diterima, sebagian, lunas
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->unique(['sales_order_id']); // Satu SO hanya satu UM penjualan
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'uang_muka_persen')) {
                $table->dropColumn('uang_muka_persen');
            }
        });

        Schema::dropIfExists('uang_muka_penjualan');
    }
};
