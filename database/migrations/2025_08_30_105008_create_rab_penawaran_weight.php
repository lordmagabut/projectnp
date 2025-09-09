<?php

// database/migrations/xxxx_create_rab_penawaran_weight.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('rab_penawaran_weight', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('proyek_id');
      $t->unsignedBigInteger('penawaran_id');
      $t->unsignedBigInteger('rab_header_id');               // header TOP-LEVEL
      $t->unsignedBigInteger('rab_penawaran_section_id')->nullable();
      $t->unsignedBigInteger('rab_penawaran_item_id')->nullable();
      $t->enum('level',['header','item']);
      $t->decimal('gross_value',15,2);
      $t->decimal('weight_pct_project',8,4);
      $t->decimal('weight_pct_in_header',8,4)->nullable();
      $t->timestamp('computed_at')->nullable();
      $t->timestamps();

      $t->index(['proyek_id']);
      $t->index(['penawaran_id']);
      $t->index(['rab_header_id']);
      $t->unique(['penawaran_id','rab_penawaran_item_id','level'],'uq_pw_item');
    });
  }
  public function down(): void {
    Schema::dropIfExists('rab_penawaran_weight');
  }
};
