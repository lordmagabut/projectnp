<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ========== rab_detail ==========
        Schema::table('rab_detail', function (Blueprint $t) {
            // Sumber harga (manual/import/ahsp)
            $t->enum('sumber_harga', ['import','manual','ahsp'])
              ->default('manual')
              ->after('ahsp_id');

            // Harga satuan komponen (snapshot/input/import)
            $t->decimal('harga_material', 16, 2)->nullable()->after('volume');
            $t->decimal('harga_upah',     16, 2)->nullable()->after('harga_material');

            // Override manual harga satuan gabungan (jika dinego/khusus)
            $t->decimal('harga_satuan_manual', 16, 2)->nullable()->after('harga_upah');

            // Total per komponen (material & upah) — untuk performa & laporan
            $t->decimal('total_material', 18, 2)->nullable()->after('harga_satuan');
            $t->decimal('total_upah',     18, 2)->nullable()->after('total_material');
        });

        // ========== rab_header ==========
        Schema::table('rab_header', function (Blueprint $t) {
            // Rekap agregat per header (bisa diisi dari sum detail)
            $t->decimal('nilai_material', 18, 2)->default(0)->after('deskripsi');
            $t->decimal('nilai_upah',     18, 2)->default(0)->after('nilai_material');
        });

        // ========== BACKFILL DATA ==========
        // 1) Isi harga_satuan (gabungan) jika belum konsisten, lalu total per baris
        //    harga_satuan = COALESCE(harga_satuan_manual, harga_material + harga_upah)
        DB::statement("
            UPDATE rab_detail
            SET
                harga_satuan   = COALESCE(harga_satuan_manual, COALESCE(harga_material,0) + COALESCE(harga_upah,0)),
                total_material = COALESCE(harga_material,0) * COALESCE(volume,0),
                total_upah     = COALESCE(harga_upah,0)     * COALESCE(volume,0),
                total          = COALESCE(COALESCE(harga_satuan_manual, COALESCE(harga_material,0) + COALESCE(harga_upah,0)),0) * COALESCE(volume,0)
        ");

        // 2) Agregasi ke header: nilai_material, nilai_upah, nilai (gabungan)
        DB::statement("
            UPDATE rab_header h
            JOIN (
                SELECT rab_header_id,
                       SUM(COALESCE(total_material,0)) AS s_mat,
                       SUM(COALESCE(total_upah,0))     AS s_uph,
                       SUM(COALESCE(total,0))          AS s_tot
                FROM rab_detail
                GROUP BY rab_header_id
            ) d ON d.rab_header_id = h.id
            SET h.nilai_material = d.s_mat,
                h.nilai_upah     = d.s_uph,
                h.nilai          = d.s_tot
        ");
    }

    public function down(): void
    {
        // Rollback agregat header lebih dulu (aman, hanya drop kolom)
        Schema::table('rab_header', function (Blueprint $t) {
            $t->dropColumn(['nilai_material', 'nilai_upah']);
        });

        // Rollback kolom baru pada detail
        Schema::table('rab_detail', function (Blueprint $t) {
            $t->dropColumn([
                'sumber_harga',
                'harga_material',
                'harga_upah',
                'harga_satuan_manual',
                'total_material',
                'total_upah',
            ]);
        });

        // Tidak mengubah nilai existing pada kolom `harga_satuan` dan `total`
    }
};
