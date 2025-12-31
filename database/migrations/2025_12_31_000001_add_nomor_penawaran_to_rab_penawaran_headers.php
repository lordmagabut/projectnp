<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            if (!Schema::hasColumn('rab_penawaran_headers', 'nomor_penawaran')) {
                $table->string('nomor_penawaran', 100)->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            if (Schema::hasColumn('rab_penawaran_headers', 'nomor_penawaran')) {
                $table->dropColumn('nomor_penawaran');
            }
        });
    }
};
