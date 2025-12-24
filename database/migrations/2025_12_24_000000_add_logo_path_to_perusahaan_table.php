<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('perusahaan') && !Schema::hasColumn('perusahaan', 'logo_path')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('template_spk');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('perusahaan') && Schema::hasColumn('perusahaan', 'logo_path')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->dropColumn('logo_path');
            });
        }
    }
};
