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
        Schema::table('bapp_details', function (Blueprint $table) {
            $table->decimal('bobot_item', 6, 2)->change();
            $table->decimal('prev_pct', 6, 2)->change();
            $table->decimal('delta_pct', 6, 2)->change();
            $table->decimal('now_pct', 6, 2)->change();
            $table->decimal('prev_item_pct', 6, 2)->change();
            $table->decimal('delta_item_pct', 6, 2)->change();
            $table->decimal('now_item_pct', 6, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bapp_details', function (Blueprint $table) {
            $table->decimal('bobot_item', 10, 4)->change();
            $table->decimal('prev_pct', 10, 4)->change();
            $table->decimal('delta_pct', 10, 4)->change();
            $table->decimal('now_pct', 10, 4)->change();
            $table->decimal('prev_item_pct', 10, 4)->change();
            $table->decimal('delta_item_pct', 10, 4)->change();
            $table->decimal('now_item_pct', 10, 4)->change();
        });
    }
};
