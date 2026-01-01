<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            if (!Schema::hasColumn('sertifikat_pembayaran', 'uang_muka_mode')) {
                $table->string('uang_muka_mode', 20)->default('proporsional')->after('uang_muka_persen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            if (Schema::hasColumn('sertifikat_pembayaran', 'uang_muka_mode')) {
                $table->dropColumn('uang_muka_mode');
            }
        });
    }
};
