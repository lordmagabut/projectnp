<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Pastikan PK auto-increment
        DB::statement('ALTER TABLE `coa` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // 2) Drop kolom id_perusahaan (jika ada) tanpa membutuhkan doctrine/dbal
        if (Schema::hasColumn('coa', 'id_perusahaan')) {
            // Pastikan tidak ada constraint/index yang mengikat kolom ini (biasanya tidak ada)
            DB::statement('ALTER TABLE `coa` DROP COLUMN `id_perusahaan`');
        }

        // 3) Tambah indeks yang bermanfaat untuk Nested Set
        Schema::table('coa', function (Blueprint $table) {
            // Indeks bisa diabaikan jika sudah ada—tidak fatal kalau migration dijalankan dua kali
            try { $table->index(['_lft']); } catch (\Throwable $e) {}
            try { $table->index(['_rgt']); } catch (\Throwable $e) {}
            try { $table->index(['parent_id']); } catch (\Throwable $e) {}
        });

        // 4) Unique no_akun (karena single company)
        //    Pakai raw SQL agar aman meski sebelumnya belum ada unique
        try {
            DB::statement('CREATE UNIQUE INDEX `ux_coa_no_akun` ON `coa` (`no_akun`)');
        } catch (\Throwable $e) {
            // index sudah ada — aman diabaikan
        }
    }

    public function down(): void
    {
        // Revert hal-hal yang relatif aman

        // a) Drop unique no_akun (jika ada)
        try { DB::statement('DROP INDEX `ux_coa_no_akun` ON `coa`'); } catch (\Throwable $e) {}

        // b) Drop indeks nested set (opsional)
        Schema::table('coa', function (Blueprint $table) {
            try { $table->dropIndex(['_lft']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['_rgt']); } catch (\Throwable $e) {}
            try { $table->dropIndex(['parent_id']); } catch (\Throwable $e) {}
        });

        // c) Tambah kembali kolom id_perusahaan (nullable)
        if (!Schema::hasColumn('coa', 'id_perusahaan')) {
            DB::statement('ALTER TABLE `coa` ADD `id_perusahaan` BIGINT UNSIGNED NULL AFTER `parent_id`');
        }

        // d) (Opsional) Kembalikan id tanpa AUTO_INCREMENT — biasanya tidak perlu
        // DB::statement('ALTER TABLE `coa` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
};
