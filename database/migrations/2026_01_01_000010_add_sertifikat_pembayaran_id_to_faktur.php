<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            if (!Schema::hasColumn('faktur', 'sertifikat_pembayaran_id')) {
                $table->unsignedBigInteger('sertifikat_pembayaran_id')->nullable()->after('id_po');
                $table->index('sertifikat_pembayaran_id', 'faktur_sertifikat_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            if (Schema::hasColumn('faktur', 'sertifikat_pembayaran_id')) {
                $table->dropIndex('faktur_sertifikat_idx');
                $table->dropColumn('sertifikat_pembayaran_id');
            }
        });
    }
};
