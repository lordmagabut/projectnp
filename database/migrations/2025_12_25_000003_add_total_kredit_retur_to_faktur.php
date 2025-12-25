<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            if (!Schema::hasColumn('faktur', 'total_kredit_retur')) {
                $table->decimal('total_kredit_retur', 20, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            if (Schema::hasColumn('faktur', 'total_kredit_retur')) {
                $table->dropColumn('total_kredit_retur');
            }
        });
    }
};