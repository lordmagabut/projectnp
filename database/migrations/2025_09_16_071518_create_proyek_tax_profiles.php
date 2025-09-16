<?php

// database/migrations/2025_09_16_000000_create_proyek_tax_profiles.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('proyek_tax_profiles', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('proyek_id')->index();

            // Kebijakan pajak default untuk proyek ini
            $t->boolean('is_taxable')->default(false);                  // kena PPN?
            $t->enum('ppn_mode', ['include','exclude'])->default('exclude'); // include = harga sudah PPN
            $t->decimal('ppn_rate', 6, 3)->default(11.000);             // 11.000 = 11%

            $t->boolean('apply_pph')->default(false);                   // ada potong PPh?
            $t->decimal('pph_rate', 6, 3)->default(2.000);              // 2.000 = 2%
            $t->enum('pph_base', ['dpp','subtotal'])->default('dpp');   // dasar PPh

            // Pembulatan & opsi ekstra
            $t->enum('rounding', ['HALF_UP','FLOOR','CEIL'])->default('HALF_UP');
            $t->json('extra_options')->nullable(); // extensible (mis. pajak daerah, diskon pajak, dll)

            // Manajemen versi profil
            $t->boolean('aktif')->default(true);                        // hanya 1 aktif/proyek
            $t->date('effective_from')->nullable();                     // opsional: mulai berlaku
            $t->date('effective_to')->nullable();                       // opsional: berakhir

            // Audit
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();

            $t->timestamps();

            // Constraint: hanya boleh satu baris aktif per proyek
            $t->unique(['proyek_id', 'aktif']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('proyek_tax_profiles');
    }
};
