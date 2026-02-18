<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (!Schema::hasColumn('proyek', 'keterangan_batal')) {
                $table->text('keterangan_batal')
                    ->nullable()
                    ->after('status')
                    ->comment('Alasan pembatalan proyek');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (Schema::hasColumn('proyek', 'keterangan_batal')) {
                $table->dropColumn('keterangan_batal');
            }
        });
    }
};
