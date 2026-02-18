<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter the status ENUM to include 'batal'
        DB::statement("ALTER TABLE proyek MODIFY status ENUM('perencanaan', 'berjalan', 'selesai', 'batal') NULL DEFAULT 'perencanaan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status ENUM to original values without 'batal'
        DB::statement("ALTER TABLE proyek MODIFY status ENUM('perencanaan', 'berjalan', 'selesai') NULL DEFAULT 'perencanaan'");
    }
};
