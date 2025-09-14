<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('
            ALTER TABLE `pemberi_kerja`
            MODIFY `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE `pemberi_kerja`
            MODIFY `id` BIGINT(20) UNSIGNED NOT NULL
        ');
    }
};
