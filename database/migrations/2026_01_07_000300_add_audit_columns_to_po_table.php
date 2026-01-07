<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('po', function (Blueprint $table) {
            if (!Schema::hasColumn('po', 'dibuat_oleh')) {
                $table->unsignedBigInteger('dibuat_oleh')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('po', 'dibuat_at')) {
                $table->timestamp('dibuat_at')->nullable()->after('dibuat_oleh');
            }
            if (!Schema::hasColumn('po', 'direview_oleh')) {
                $table->unsignedBigInteger('direview_oleh')->nullable()->after('dibuat_at');
            }
            if (!Schema::hasColumn('po', 'direview_at')) {
                $table->timestamp('direview_at')->nullable()->after('direview_oleh');
            }
            if (!Schema::hasColumn('po', 'disetujui_oleh')) {
                $table->unsignedBigInteger('disetujui_oleh')->nullable()->after('direview_at');
            }
            if (!Schema::hasColumn('po', 'disetujui_at')) {
                $table->timestamp('disetujui_at')->nullable()->after('disetujui_oleh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('po', function (Blueprint $table) {
            if (Schema::hasColumn('po', 'disetujui_at')) {
                $table->dropColumn('disetujui_at');
            }
            if (Schema::hasColumn('po', 'disetujui_oleh')) {
                $table->dropColumn('disetujui_oleh');
            }
            if (Schema::hasColumn('po', 'direview_at')) {
                $table->dropColumn('direview_at');
            }
            if (Schema::hasColumn('po', 'direview_oleh')) {
                $table->dropColumn('direview_oleh');
            }
            if (Schema::hasColumn('po', 'dibuat_at')) {
                $table->dropColumn('dibuat_at');
            }
            if (Schema::hasColumn('po', 'dibuat_oleh')) {
                $table->dropColumn('dibuat_oleh');
            }
        });
    }
};
