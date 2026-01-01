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
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            $table->string('price_mode', 10)->nullable()->after('status')->comment('pisah atau gabung');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sertifikat_pembayaran', function (Blueprint $table) {
            $table->dropColumn('price_mode');
        });
    }
};
