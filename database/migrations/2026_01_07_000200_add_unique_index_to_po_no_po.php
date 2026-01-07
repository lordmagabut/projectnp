<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('po')) {
            Schema::table('po', function (Blueprint $table) {
                // Add unique index if not exists
                $table->unique('no_po', 'unique_no_po');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('po')) {
            Schema::table('po', function (Blueprint $table) {
                $table->dropUnique('unique_no_po');
            });
        }
    }
};
