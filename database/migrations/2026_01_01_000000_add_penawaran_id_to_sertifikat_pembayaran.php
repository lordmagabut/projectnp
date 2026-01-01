<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            $table->unsignedBigInteger('penawaran_id')->nullable()->after('bapp_id')->comment('Reference ke penawaran untuk tracking progress lintas BAPP');
            $table->index('penawaran_id');
        });
    }

    public function down(): void {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            $table->dropIndex('sertifikat_pembayaran_penawaran_id_index');
            $table->dropColumn('penawaran_id');
        });
    }
};
