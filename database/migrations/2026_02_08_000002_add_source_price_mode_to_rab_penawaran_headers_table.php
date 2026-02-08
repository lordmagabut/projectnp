<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            if (!Schema::hasColumn('rab_penawaran_headers', 'source_price_mode')) {
                $table->string('source_price_mode', 20)->default('base')->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            if (Schema::hasColumn('rab_penawaran_headers', 'source_price_mode')) {
                $table->dropColumn('source_price_mode');
            }
        });
    }
};
