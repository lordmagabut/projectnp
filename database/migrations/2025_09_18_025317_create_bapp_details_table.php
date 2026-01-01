<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('bapp_details', function (Blueprint $t) {
      $t->engine = 'InnoDB';

      $t->id();

      $t->unsignedBigInteger('bapp_id');
      $t->unsignedBigInteger('rab_detail_id')->nullable(); // tabel Anda: rab_detail

      // snapshot identitas item
      $t->string('kode')->nullable();
      $t->text('uraian')->nullable();

      // snapshot bobot/progress (% proyek) - 2 desimal untuk konsistensi dengan sertifikat
      $t->decimal('bobot_item',   6, 2)->default(0);
      $t->decimal('prev_pct',     6, 2)->default(0);
      $t->decimal('delta_pct',    6, 2)->default(0);
      $t->decimal('now_pct',      6, 2)->default(0);

      // opsional: % terhadap item
      $t->decimal('prev_item_pct', 6, 2)->default(0);
      $t->decimal('delta_item_pct', 6, 2)->default(0);
      $t->decimal('now_item_pct',  6, 2)->default(0);

      $t->timestamps();

      // FK
      $t->foreign('bapp_id')
        ->references('id')->on('bapps')
        ->onDelete('cascade');

      $t->foreign('rab_detail_id')
        ->references('id')->on('rab_detail')
        ->onDelete('set null');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('bapp_details');
  }
};
