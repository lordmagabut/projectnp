<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rab_progress_detail', function (Blueprint $table) {
            $table->decimal('bobot_minggu_ini', 6, 2)->change();
            $table->decimal('bobot_item_snapshot', 6, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rab_progress_detail', function (Blueprint $table) {
            $table->decimal('bobot_minggu_ini', 10, 4)->change();
            $table->decimal('bobot_item_snapshot', 10, 4)->change();
        });
    }
};
