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
        // Update tabel bapps
        if (Schema::hasTable('bapps') && !Schema::hasColumn('bapps', 'is_final_account')) {
            Schema::table('bapps', function (Blueprint $table) {
                $table->boolean('is_final_account')->default(false)->after('status');
                $table->decimal('nilai_kontrak_total', 15, 2)->nullable()->after('is_final_account');
                $table->decimal('nilai_realisasi_total', 15, 2)->nullable()->after('nilai_kontrak_total');
                $table->decimal('nilai_adjustment', 15, 2)->nullable()->after('nilai_realisasi_total');
                $table->text('final_account_notes')->nullable()->after('nilai_adjustment');
            });
        }

        // Update tabel bapp_details
        if (Schema::hasTable('bapp_details') && !Schema::hasColumn('bapp_details', 'qty')) {
            Schema::table('bapp_details', function (Blueprint $table) {
                $table->decimal('qty', 15, 4)->nullable()->after('uraian');
                $table->string('satuan', 50)->nullable()->after('qty');
                $table->decimal('harga', 15, 2)->nullable()->after('satuan');
                $table->decimal('qty_kontrak', 15, 4)->nullable()->after('harga');
                $table->decimal('qty_realisasi', 15, 4)->nullable()->after('qty_kontrak');
                $table->decimal('nilai_kontrak', 15, 2)->nullable()->after('qty_realisasi');
                $table->decimal('nilai_realisasi', 15, 2)->nullable()->after('nilai_kontrak');
                $table->decimal('nilai_adjustment', 15, 2)->nullable()->after('nilai_realisasi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bapps')) {
            Schema::table('bapps', function (Blueprint $table) {
                $table->dropColumn(['is_final_account', 'nilai_kontrak_total', 'nilai_realisasi_total', 'nilai_adjustment', 'final_account_notes']);
            });
        }

        if (Schema::hasTable('bapp_details')) {
            Schema::table('bapp_details', function (Blueprint $table) {
                $table->dropColumn(['qty', 'satuan', 'harga', 'qty_kontrak', 'qty_realisasi', 'nilai_kontrak', 'nilai_realisasi', 'nilai_adjustment']);
            });
        }
    }
};
