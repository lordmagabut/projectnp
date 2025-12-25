<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (!Schema::hasColumn('perusahaan', 'coa_hutang_usaha_id')) {
                $table->unsignedBigInteger('coa_hutang_usaha_id')->nullable()->after('nama_perusahaan');
            }
            if (!Schema::hasColumn('perusahaan', 'coa_ppn_masukan_id')) {
                $table->unsignedBigInteger('coa_ppn_masukan_id')->nullable()->after('coa_hutang_usaha_id');
            }
            if (!Schema::hasColumn('perusahaan', 'coa_kas_id')) {
                $table->unsignedBigInteger('coa_kas_id')->nullable()->after('coa_ppn_masukan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            if (Schema::hasColumn('perusahaan', 'coa_hutang_usaha_id')) {
                $table->dropColumn('coa_hutang_usaha_id');
            }
            if (Schema::hasColumn('perusahaan', 'coa_ppn_masukan_id')) {
                $table->dropColumn('coa_ppn_masukan_id');
            }
            if (Schema::hasColumn('perusahaan', 'coa_kas_id')) {
                $table->dropColumn('coa_kas_id');
            }
        });
    }
};
