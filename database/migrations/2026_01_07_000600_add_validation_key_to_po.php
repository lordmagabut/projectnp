<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValidationKeyToPo extends Migration
{
    public function up()
    {
        Schema::table('po', function (Blueprint $table) {
            if (!Schema::hasColumn('po', 'validation_key')) {
                $table->string('validation_key')->nullable()->unique()->after('no_po');
            }
        });
    }

    public function down()
    {
        Schema::table('po', function (Blueprint $table) {
            if (Schema::hasColumn('po', 'validation_key')) {
                $table->dropColumn('validation_key');
            }
        });
    }
}
