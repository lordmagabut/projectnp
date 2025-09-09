<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rab_schedule_meta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyek_id');
            $table->unsignedBigInteger('penawaran_id'); // -> rab_penawaran_header.id
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_weeks'); // cache, dihitung dari start/end
            $table->timestamps();

            $table->foreign('proyek_id')->references('id')->on('proyek')->onDelete('cascade');
            $table->foreign('penawaran_id')->references('id')->on('rab_penawaran_headers')->onDelete('cascade');
            $table->unique(['proyek_id','penawaran_id']); // 1 meta per penawaran
        });
    }
    public function down(): void {
        Schema::dropIfExists('rab_schedule_meta');
    }
};
