<?php

// database/migrations/xxxx_add_delta_cols_to_sertifikat_pembayaran.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('sertifikat_pembayaran', function (Blueprint $t) {
      $t->decimal('persen_progress_prev',   6,2)->default(0);
      $t->decimal('persen_progress_delta',  6,2)->default(0);
      // Opsional: simpan subtotal kumulatif utk audit
      $t->decimal('subtotal_cum', 20,2)->default(0);
      $t->decimal('subtotal_prev_cum', 20,2)->default(0);
    });
  }
  public function down(): void {
    Schema::table('sertifikat_pembayaran', function (Blueprint $t) {
      $t->dropColumn(['persen_progress_prev','persen_progress_delta','subtotal_cum','subtotal_prev_cum']);
    });
  }
};
