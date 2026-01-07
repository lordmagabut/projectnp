<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditTrailToPenerimaanPembelian extends Migration
{
    public function up()
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (!Schema::hasColumn('penerimaan_pembelian', 'dibuat_oleh')) {
                $table->unsignedBigInteger('dibuat_oleh')->nullable()->after('id_perusahaan');
            }
            if (!Schema::hasColumn('penerimaan_pembelian', 'dibuat_at')) {
                $table->timestamp('dibuat_at')->nullable()->after('dibuat_oleh');
            }
            if (!Schema::hasColumn('penerimaan_pembelian', 'disetujui_oleh')) {
                $table->unsignedBigInteger('disetujui_oleh')->nullable()->after('dibuat_at');
            }
            if (!Schema::hasColumn('penerimaan_pembelian', 'disetujui_at')) {
                $table->timestamp('disetujui_at')->nullable()->after('disetujui_oleh');
            }
        });
    }

    public function down()
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_pembelian', 'dibuat_oleh')) {
                $table->dropColumn('dibuat_oleh');
            }
            if (Schema::hasColumn('penerimaan_pembelian', 'dibuat_at')) {
                $table->dropColumn('dibuat_at');
            }
            if (Schema::hasColumn('penerimaan_pembelian', 'disetujui_oleh')) {
                $table->dropColumn('disetujui_oleh');
            }
            if (Schema::hasColumn('penerimaan_pembelian', 'disetujui_at')) {
                $table->dropColumn('disetujui_at');
            }
        });
    }
}
