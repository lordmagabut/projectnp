<?php

// database/migrations/2025_09_09_000001_add_pct_to_rab_progress_detail.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rab_progress_detail', function (Blueprint $t) {
            // delta % proyek (sudah ada di skema kamu; biarkan)
            // $t->decimal('bobot_minggu_ini', 8, 4)->default(0.0000)->change();

            // delta % item (baru)
            $t->decimal('pct_item_minggu_ini', 6, 2)->default(0.00)->after('bobot_minggu_ini');

            // snapshot bobot item saat submit (baru & sangat dianjurkan)
            $t->decimal('bobot_item_snapshot', 8, 4)->default(0.0000)->after('pct_item_minggu_ini');

            $t->index(['rab_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rab_progress_detail', function (Blueprint $t) {
            $t->dropColumn(['pct_item_minggu_ini','bobot_item_snapshot']);
        });
    }
};
