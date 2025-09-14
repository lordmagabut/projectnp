<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pemberi_kerja', function (Blueprint $table) {
            $table->string('jabatan_pic', 100)->nullable()->after('pic');
            $table->string('nama_direktur', 255)->nullable()->after('jabatan_pic');
            $table->string('jabatan_direktur', 100)->nullable()->after('nama_direktur');
        });
    }

    public function down(): void
    {
        Schema::table('pemberi_kerja', function (Blueprint $table) {
            $table->dropColumn(['jabatan_pic', 'nama_direktur', 'jabatan_direktur']);
        });
    }
};
