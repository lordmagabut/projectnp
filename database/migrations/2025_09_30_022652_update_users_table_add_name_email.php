<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom baru bila belum ada
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name', 255)->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                $table->string('remember_token', 100)->nullable()->after('password');
            }
        });

        // 2) Isi awal "name" dari "username" agar konsisten dengan UI & Model
        DB::table('users')
            ->whereNull('name')
            ->update(['name' => DB::raw('username')]);

        // 3) Ubah kolom id menjadi AUTO_INCREMENT (gunakan raw SQL agar tidak butuh doctrine/dbal)
        DB::statement('ALTER TABLE `users` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // 4) Standarkan UNIQUE INDEX untuk username
        //    Cek apakah sudah ada index bernama users_username_unique; bila tidak, normalisasi.
        $usernameIndex = DB::selectOne("
            SELECT INDEX_NAME as name
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'username'
            GROUP BY INDEX_NAME
            LIMIT 1
        ");

        if ($usernameIndex && $usernameIndex->name !== 'users_username_unique') {
            // Drop index lama lalu tambah yang baru
            DB::statement("ALTER TABLE `users` DROP INDEX `{$usernameIndex->name}`");
        }

        // Tambah unique username jika belum ada
        $hasUsersUsernameUnique = DB::selectOne("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'users_username_unique'
            LIMIT 1
        ");
        if (!$hasUsersUsernameUnique) {
            DB::statement("ALTER TABLE `users` ADD UNIQUE KEY `users_username_unique` (`username`)");
        }

        // 5) Tambah UNIQUE INDEX untuk email (NULL diperbolehkan; MySQL mengizinkan banyak NULL pada unique)
        $hasUsersEmailUnique = DB::selectOne("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'users_email_unique'
            LIMIT 1
        ");
        if (!$hasUsersEmailUnique) {
            DB::statement("ALTER TABLE `users` ADD UNIQUE KEY `users_email_unique` (`email`)");
        }
    }

    public function down(): void
    {
        // 1) Hapus UNIQUE email kalau ada
        $hasUsersEmailUnique = DB::selectOne("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'users_email_unique'
            LIMIT 1
        ");
        if ($hasUsersEmailUnique) {
            DB::statement("ALTER TABLE `users` DROP INDEX `users_email_unique`");
        }

        // 2) Kembalikan index username ke nama generik 'username' (opsional)
        $hasUsersUsernameUnique = DB::selectOne("
            SELECT 1
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'users'
              AND INDEX_NAME = 'users_username_unique'
            LIMIT 1
        ");
        if ($hasUsersUsernameUnique) {
            // Tambahkan index 'username' kalau belum ada, lalu drop yang standar
            $hasGeneric = DB::selectOne("
                SELECT 1
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = 'users'
                  AND INDEX_NAME = 'username'
                LIMIT 1
            ");
            if (!$hasGeneric) {
                DB::statement("ALTER TABLE `users` ADD UNIQUE KEY `username` (`username`)");
            }
            DB::statement("ALTER TABLE `users` DROP INDEX `users_username_unique`");
        }

        // 3) Drop kolom tambahan (safe checks)
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }
        });

        // 4) OPTIONAL: kembalikan id ke NON AUTO_INCREMENT (tidak wajib, tapi simetris)
        //    Catatan: setelah ini insert user baru perlu dihandle manual id-nya.
        DB::statement('ALTER TABLE `users` MODIFY `id` BIGINT UNSIGNED NOT NULL');
    }
};
