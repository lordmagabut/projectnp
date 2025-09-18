<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            // Keterangan (Term of Payment)
            if (!Schema::hasColumn('rab_penawaran_headers', 'keterangan')) {
                $table->text('keterangan')->nullable()->after('spesifikasi');
            }

            // Multi dokumen PDF (simpan array path)
            if (!Schema::hasColumn('rab_penawaran_headers', 'approval_doc_paths')) {
                $table->json('approval_doc_paths')->nullable()->after('keterangan');
            }

            // Waktu disetujui (final)
            if (!Schema::hasColumn('rab_penawaran_headers', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_doc_paths');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            if (Schema::hasColumn('rab_penawaran_headers', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('rab_penawaran_headers', 'approval_doc_paths')) {
                $table->dropColumn('approval_doc_paths');
            }
            if (Schema::hasColumn('rab_penawaran_headers', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};
