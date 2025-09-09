<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('rab_schedule_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('penawaran_id')->after('proyek_id');
            $table->unsignedBigInteger('rab_penawaran_item_id')->nullable()->after('rab_header_id');

            $table->foreign('penawaran_id')
                  ->references('id')->on('rab_penawaran_headers')->onDelete('cascade');
            $table->foreign('rab_penawaran_item_id')
                  ->references('id')->on('rab_penawaran_items')->onDelete('cascade');

            $table->index(['proyek_id','penawaran_id','minggu_ke']);
        });
    }
    public function down(): void {
        Schema::table('rab_schedule_detail', function (Blueprint $table) {
            $table->dropForeign(['penawaran_id']);
            $table->dropForeign(['rab_penawaran_item_id']);
            $table->dropColumn(['penawaran_id','rab_penawaran_item_id']);
        });
    }
};
