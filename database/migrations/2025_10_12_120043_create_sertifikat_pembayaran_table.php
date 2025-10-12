<?php
// database/migrations/2025_10_12_000000_create_sertifikat_pembayaran_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('sertifikat_pembayaran', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('bapp_id')->index();
      $t->string('nomor')->unique();                // Nomor Sertifikat
      $t->date('tanggal');
      $t->unsignedInteger('termin_ke')->default(1);
      $t->decimal('persen_progress', 6, 2)->default(0.00);  // mis. 31.28

      // Snapshot nilai (disimpan agar stabil sesuai dokumen saat diterbitkan)
      $t->decimal('nilai_wo_material', 20, 2)->default(0);
      $t->decimal('nilai_wo_jasa', 20, 2)->default(0);
      $t->decimal('nilai_wo_total', 20, 2)->default(0);

      $t->decimal('uang_muka_persen', 6, 2)->default(0);       // mis. 25.00
      $t->decimal('uang_muka_nilai', 20, 2)->default(0);
      $t->decimal('pemotongan_um_persen', 6, 2)->default(0);   // mis. 31.28
      $t->decimal('pemotongan_um_nilai', 20, 2)->default(0);
      $t->decimal('sisa_uang_muka', 20, 2)->default(0);

      $t->decimal('nilai_progress_rp', 20, 2)->default(0);     // nilai progress rupiah (persen x WO total)
      $t->decimal('retensi_persen', 6, 2)->default(0);         // mis. 5.00
      $t->decimal('retensi_nilai', 20, 2)->default(0);

      $t->decimal('total_dibayar', 20, 2)->default(0);         // Progress - potongan UM - retensi
      $t->decimal('ppn_persen', 6, 2)->default(11.00);
      $t->decimal('ppn_nilai', 20, 2)->default(0);
      $t->decimal('total_tagihan', 20, 2)->default(0);

      $t->string('terbilang')->nullable();

      // Pihak terkait (opsional)
      $t->string('pemberi_tugas_nama')->nullable();
      $t->string('pemberi_tugas_perusahaan')->nullable();
      $t->string('pemberi_tugas_jabatan')->nullable();
      $t->string('penerima_tugas_nama')->nullable();
      $t->string('penerima_tugas_perusahaan')->nullable();
      $t->string('penerima_tugas_jabatan')->nullable();

      $t->unsignedBigInteger('dibuat_oleh_id')->nullable();
      $t->unsignedBigInteger('disetujui_oleh_id')->nullable();

      $t->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('sertifikat_pembayaran');
  }
};
