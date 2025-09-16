<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            $table->longText('keterangan')->nullable()->after('status');
        });
    }
    public function down(): void {
        Schema::table('rab_penawaran_headers', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};