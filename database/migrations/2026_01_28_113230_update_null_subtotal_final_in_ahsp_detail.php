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
        // Update all NULL subtotal_final to equal subtotal
        // This handles old AHSP data created before diskon/ppn feature
        \DB::statement('UPDATE ahsp_detail 
            SET subtotal_final = subtotal,
                diskon_persen = 0,
                ppn_persen = 0,
                diskon_nominal = 0,
                ppn_nominal = 0
            WHERE subtotal_final IS NULL OR subtotal_final = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: set subtotal_final back to NULL for records that were updated
        // This is only for development; in production, keep the data
        // DB::table('ahsp_detail')->update(['subtotal_final' => null]);
    }
};
