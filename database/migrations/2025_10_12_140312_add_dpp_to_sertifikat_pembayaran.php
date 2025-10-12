<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('sertifikat_pembayaran', function (Blueprint $t) {
      $t->decimal('dpp_material', 20, 2)->default(0)->after('total_dibayar');
      $t->decimal('dpp_jasa', 20, 2)->default(0)->after('dpp_material');
    });
  }
  public function down(): void {
    Schema::table('sertifikat_pembayaran', function (Blueprint $t) {
      $t->dropColumn(['dpp_material','dpp_jasa']);
    });
  }
};
