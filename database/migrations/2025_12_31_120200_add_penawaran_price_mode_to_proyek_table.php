<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (!Schema::hasColumn('proyek', 'penawaran_price_mode')) {
                $table->string('penawaran_price_mode', 20)
                    ->default('pisah')
                    ->after('jenis_proyek');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (Schema::hasColumn('proyek', 'penawaran_price_mode')) {
                $table->dropColumn('penawaran_price_mode');
            }
        });
    }
};
