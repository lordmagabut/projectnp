<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('bapps', function (Blueprint $t) {
      $t->engine = 'InnoDB';

      $t->id();

      // FK ke tabel yang ada di DB Anda
      $t->unsignedBigInteger('proyek_id');
      $t->unsignedBigInteger('penawaran_id')->nullable(); // rab_penawaran_headers
      $t->unsignedBigInteger('progress_id')->nullable();  // rab_progress

      $t->unsignedInteger('minggu_ke');
      $t->date('tanggal_bapp');
      $t->string('nomor_bapp', 100)->unique();
      $t->enum('status', ['draft','submitted','approved','rejected'])->default('draft');

      $t->decimal('total_prev_pct', 6, 2)->default(0);
      $t->decimal('total_delta_pct', 6, 2)->default(0);
      $t->decimal('total_now_pct', 6, 2)->default(0);

      $t->string('file_pdf_path')->nullable();

      $t->unsignedBigInteger('created_by')->nullable();   // users
      $t->unsignedBigInteger('approved_by')->nullable();  // users
      $t->timestamp('approved_at')->nullable();

      $t->text('notes')->nullable();

      $t->timestamps();

      // ===== Foreign keys (explicit) =====
      $t->foreign('proyek_id')
        ->references('id')->on('proyek')      // <<== SINGULAR
        ->onDelete('cascade');

      $t->foreign('penawaran_id')
        ->references('id')->on('rab_penawaran_headers')
        ->onDelete('set null');

      $t->foreign('progress_id')
        ->references('id')->on('rab_progress')
        ->onDelete('set null');

      $t->foreign('created_by')
        ->references('id')->on('users')
        ->onDelete('set null');

      $t->foreign('approved_by')
        ->references('id')->on('users')
        ->onDelete('set null');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('bapps');
  }
};
