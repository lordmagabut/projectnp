<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('basts', function (Blueprint $table) {
            $table->json('ketentuan')->nullable()->after('file_bast_pdf');
        });
    }

    public function down()
    {
        Schema::table('basts', function (Blueprint $table) {
            $table->dropColumn('ketentuan');
        });
    }
};
