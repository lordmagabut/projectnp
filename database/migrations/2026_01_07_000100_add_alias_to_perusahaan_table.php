<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('perusahaan') && !Schema::hasColumn('perusahaan', 'alias')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->string('alias', 100)->nullable()->after('nama_perusahaan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('perusahaan') && Schema::hasColumn('perusahaan', 'alias')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->dropColumn('alias');
            });
        }
    }
};
