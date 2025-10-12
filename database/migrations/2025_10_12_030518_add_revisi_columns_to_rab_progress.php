<?php
// database/migrations/2025_10_12_000001_add_revisi_columns_to_rab_progress.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rab_progress', function (Blueprint $t) {
            // Tambahkan kolom tepat setelah id
            $t->unsignedBigInteger('revisi_dari_id')->nullable()->after('id');
            $t->unsignedBigInteger('revisi_ke_id')->nullable()->after('revisi_dari_id');

            // Index (optional; foreign() otomatis buat index, tapi eksplisit tidak masalah)
            $t->index('revisi_dari_id', 'rabprog_revisi_dari_idx');
            $t->index('revisi_ke_id', 'rabprog_revisi_ke_idx');
        });

        // Pisahkan penambahan FK agar kompatibel dengan beberapa driver
        Schema::table('rab_progress', function (Blueprint $t) {
            // Self-referencing FKs, aman dihapus -> SET NULL
            $t->foreign('revisi_dari_id', 'fk_rabprog_revisi_dari')
              ->references('id')->on('rab_progress')
              ->onDelete('set null');

            $t->foreign('revisi_ke_id', 'fk_rabprog_revisi_ke')
              ->references('id')->on('rab_progress')
              ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Lepas FK dulu, baru kolom
        Schema::table('rab_progress', function (Blueprint $t) {
            // Hapus FK dengan nama yang sama seperti saat up()
            $t->dropForeign('fk_rabprog_revisi_dari');
            $t->dropForeign('fk_rabprog_revisi_ke');

            // Hapus index (kalau kamu membuatnya eksplisit)
            $t->dropIndex('rabprog_revisi_dari_idx');
            $t->dropIndex('rabprog_revisi_ke_idx');

            // Hapus kolom
            $t->dropColumn(['revisi_dari_id', 'revisi_ke_id']);
        });
    }
};
