<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('rab_progress')) {
            Schema::table('rab_progress', function (Blueprint $t) {
                $t->unsignedBigInteger('penawaran_id')->nullable()->index()->after('proyek_id');
                // FK opsional (hapus komentar kalau tabel & PK cocok):
                // $t->foreign('penawaran_id')->references('id')->on('rab_penawaran_headers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('rab_progress_details')) {
            Schema::table('rab_progress_details', function (Blueprint $t) {
                $t->unsignedBigInteger('penawaran_id')->nullable()->index()->after('proyek_id');
                // $t->foreign('penawaran_id')->references('id')->on('rab_penawaran_headers')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rab_progress_details') && Schema::hasColumn('rab_progress_details','penawaran_id')) {
            Schema::table('rab_progress_details', function (Blueprint $t) {
                // $t->dropForeign(['penawaran_id']);
                $t->dropColumn('penawaran_id');
            });
        }

        if (Schema::hasTable('rab_progress') && Schema::hasColumn('rab_progress','penawaran_id')) {
            Schema::table('rab_progress', function (Blueprint $t) {
                // $t->dropForeign(['penawaran_id']);
                $t->dropColumn('penawaran_id');
            });
        }
    }
};
