<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (!Schema::hasColumn('proyek', 'kontingensi_persen')) {
                $table->decimal('kontingensi_persen', 6, 2)->default(0)->after('diskon_rab');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (Schema::hasColumn('proyek', 'kontingensi_persen')) {
                $table->dropColumn('kontingensi_persen');
            }
        });
    }
};
