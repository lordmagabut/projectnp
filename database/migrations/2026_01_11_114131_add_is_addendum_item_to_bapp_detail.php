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
        if (Schema::hasTable('bapp_details') && !Schema::hasColumn('bapp_details', 'is_addendum_item')) {
            Schema::table('bapp_details', function (Blueprint $table) {
                $table->boolean('is_addendum_item')->default(false)->after('nilai_adjustment');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bapp_details') && Schema::hasColumn('bapp_details', 'is_addendum_item')) {
            Schema::table('bapp_details', function (Blueprint $table) {
                $table->dropColumn('is_addendum_item');
            });
        }
    }
};
