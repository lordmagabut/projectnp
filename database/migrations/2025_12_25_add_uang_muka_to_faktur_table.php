<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            // Add column untuk tracking UM yang dipakai
            if (!Schema::hasColumn('faktur', 'uang_muka_dipakai')) {
                $table->decimal('uang_muka_dipakai', 20, 2)->default(0)->after('total');
            }
            
            // Add column untuk referensi ke uang_muka_pembelian
            if (!Schema::hasColumn('faktur', 'uang_muka_id')) {
                $table->unsignedBigInteger('uang_muka_id')->nullable()->after('uang_muka_dipakai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            $table->dropColumn(['uang_muka_dipakai', 'uang_muka_id']);
        });
    }
};
