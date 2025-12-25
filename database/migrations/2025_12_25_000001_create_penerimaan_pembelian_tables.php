<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenerimaanPembelianTables extends Migration
{
    public function up()
    {
        // Tambah kolom qty_diterima dan qty_diretur di po_detail DULU
        if (Schema::hasTable('po_detail')) {
            Schema::table('po_detail', function (Blueprint $table) {
                if (!Schema::hasColumn('po_detail', 'qty_diterima')) {
                    $table->decimal('qty_diterima', 15, 2)->default(0)->after('qty_terfaktur');
                }
                if (!Schema::hasColumn('po_detail', 'qty_diretur')) {
                    $table->decimal('qty_diretur', 15, 2)->default(0)->after('qty_diterima');
                }
            });
        }

        // Tabel Header Penerimaan Pembelian (tanpa foreign key dulu)
        Schema::create('penerimaan_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_penerimaan')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('po_id')->nullable();
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->string('nama_supplier');
            $table->unsignedBigInteger('id_proyek')->nullable();
            $table->unsignedBigInteger('id_perusahaan')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('no_surat_jalan')->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->timestamps();
            
            // Index untuk performa
            $table->index('po_id');
            $table->index('id_supplier');
            $table->index('id_proyek');
        });

        // Tabel Detail Penerimaan Pembelian
        Schema::create('penerimaan_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penerimaan_id');
            $table->unsignedBigInteger('po_detail_id')->nullable();
            $table->string('kode_item');
            $table->string('uraian');
            $table->decimal('qty_po', 15, 2);           // Qty dari PO
            $table->decimal('qty_diterima', 15, 2);     // Qty yang diterima
            $table->string('uom');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('penerimaan_id')->references('id')->on('penerimaan_pembelian')->onDelete('cascade');
            $table->index('po_detail_id');
        });

        // Tabel Header Retur Pembelian
        Schema::create('retur_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur')->unique();
            $table->date('tanggal');
            $table->unsignedBigInteger('penerimaan_id');
            $table->unsignedBigInteger('id_supplier')->nullable();
            $table->string('nama_supplier');
            $table->unsignedBigInteger('id_proyek')->nullable();
            $table->unsignedBigInteger('id_perusahaan')->nullable();
            $table->text('alasan')->nullable();
            $table->enum('status', ['draft', 'approved'])->default('draft');
            $table->unsignedBigInteger('jurnal_id')->nullable(); // Untuk jurnal retur
            $table->timestamps();

            $table->foreign('penerimaan_id')->references('id')->on('penerimaan_pembelian')->onDelete('cascade');
            $table->index('id_supplier');
            $table->index('jurnal_id');
        });

        // Tabel Detail Retur Pembelian
        Schema::create('retur_pembelian_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('retur_id');
            $table->unsignedBigInteger('penerimaan_detail_id');
            $table->string('kode_item');
            $table->string('uraian');
            $table->decimal('qty_retur', 15, 2);
            $table->string('uom');
            $table->decimal('harga', 15, 2);            // Harga satuan dari PO
            $table->decimal('total', 15, 2);            // Total nilai retur
            $table->text('alasan')->nullable();
            $table->timestamps();

            $table->foreign('retur_id')->references('id')->on('retur_pembelian')->onDelete('cascade');
            $table->foreign('penerimaan_detail_id')->references('id')->on('penerimaan_pembelian_detail')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('retur_pembelian_detail');
        Schema::dropIfExists('retur_pembelian');
        Schema::dropIfExists('penerimaan_pembelian_detail');
        Schema::dropIfExists('penerimaan_pembelian');

        if (Schema::hasTable('po_detail')) {
            Schema::table('po_detail', function (Blueprint $table) {
                if (Schema::hasColumn('po_detail', 'qty_diterima')) {
                    $table->dropColumn('qty_diterima');
                }
                if (Schema::hasColumn('po_detail', 'qty_diretur')) {
                    $table->dropColumn('qty_diretur');
                }
            });
        }
    }
}
