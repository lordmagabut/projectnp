<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            $table->enum('uang_muka_mode', ['proporsional', 'utuh'])
                ->default('proporsional')
                ->after('penawaran_price_mode');
        });
    }

    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (Schema::hasColumn('proyek', 'uang_muka_mode')) {
                $table->dropColumn('uang_muka_mode');
            }
        });
    }
};
