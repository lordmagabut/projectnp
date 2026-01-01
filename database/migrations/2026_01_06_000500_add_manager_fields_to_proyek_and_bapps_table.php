<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyek', function (Blueprint $table) {
            if (!Schema::hasColumn('proyek', 'site_manager_name')) {
                $table->string('site_manager_name')->nullable()->after('lokasi');
            }
            if (!Schema::hasColumn('proyek', 'project_manager_name')) {
                $table->string('project_manager_name')->nullable()->after('site_manager_name');
            }
        });

        Schema::table('bapps', function (Blueprint $table) {
            if (!Schema::hasColumn('bapps', 'sign_by')) {
                $table->enum('sign_by', ['sm', 'pm'])->default('sm')->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bapps', function (Blueprint $table) {
            if (Schema::hasColumn('bapps', 'sign_by')) {
                $table->dropColumn('sign_by');
            }
        });

        Schema::table('proyek', function (Blueprint $table) {
            if (Schema::hasColumn('proyek', 'project_manager_name')) {
                $table->dropColumn('project_manager_name');
            }
            if (Schema::hasColumn('proyek', 'site_manager_name')) {
                $table->dropColumn('site_manager_name');
            }
        });
    }
};
