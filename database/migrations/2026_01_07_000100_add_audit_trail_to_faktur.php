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
        Schema::table('faktur', function (Blueprint $table) {
            if (!Schema::hasColumn('faktur', 'dibuat_oleh')) {
                $table->unsignedBigInteger('dibuat_oleh')->nullable()->after('status');
            }
            if (!Schema::hasColumn('faktur', 'dibuat_at')) {
                $table->timestamp('dibuat_at')->nullable()->after('dibuat_oleh');
            }
            if (!Schema::hasColumn('faktur', 'disetujui_oleh')) {
                $table->unsignedBigInteger('disetujui_oleh')->nullable()->after('dibuat_at');
            }
            if (!Schema::hasColumn('faktur', 'disetujui_at')) {
                $table->timestamp('disetujui_at')->nullable()->after('disetujui_oleh');
            }

            // Foreign keys
            if (!Schema::hasColumn('faktur', 'dibuat_oleh')) {
                $table->foreign('dibuat_oleh')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('faktur', 'disetujui_oleh')) {
                $table->foreign('disetujui_oleh')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faktur', function (Blueprint $table) {
            if (Schema::hasColumn('faktur', 'dibuat_oleh')) {
                $table->dropForeign(['dibuat_oleh']);
                $table->dropColumn('dibuat_oleh');
            }
            if (Schema::hasColumn('faktur', 'dibuat_at')) {
                $table->dropColumn('dibuat_at');
            }
            if (Schema::hasColumn('faktur', 'disetujui_oleh')) {
                $table->dropForeign(['disetujui_oleh']);
                $table->dropColumn('disetujui_oleh');
            }
            if (Schema::hasColumn('faktur', 'disetujui_at')) {
                $table->dropColumn('disetujui_at');
            }
        });
    }
};
