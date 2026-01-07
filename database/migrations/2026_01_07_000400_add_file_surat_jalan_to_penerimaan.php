<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileSuratJalanToPenerimaan extends Migration
{
    public function up()
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (!Schema::hasColumn('penerimaan_pembelian', 'file_surat_jalan')) {
                $table->string('file_surat_jalan')->nullable()->after('no_surat_jalan');
            }
        });
    }

    public function down()
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_pembelian', 'file_surat_jalan')) {
                $table->dropColumn('file_surat_jalan');
            }
        });
    }
}
