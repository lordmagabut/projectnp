<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (!Schema::hasColumn('penerimaan_pembelian', 'status_penagihan')) {
                $table->enum('status_penagihan', ['belum','sebagian','lunas'])->default('belum')->after('status');
            }
        });

        Schema::table('penerimaan_pembelian_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('penerimaan_pembelian_detail', 'qty_terfaktur')) {
                $table->decimal('qty_terfaktur', 15, 2)->default(0)->after('qty_diterima');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_pembelian', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_pembelian', 'status_penagihan')) {
                $table->dropColumn('status_penagihan');
            }
        });

        Schema::table('penerimaan_pembelian_detail', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_pembelian_detail', 'qty_terfaktur')) {
                $table->dropColumn('qty_terfaktur');
            }
        });
    }
};
