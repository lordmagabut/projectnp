<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiskonPpnToAhspDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ahsp_detail', function (Blueprint $table) {
            $table->decimal('diskon_persen', 5, 2)->default(0)->after('subtotal')->comment('Diskon dalam persen (%)');
            $table->decimal('ppn_persen', 5, 2)->default(0)->after('diskon_persen')->comment('PPN dalam persen (%)');
            $table->decimal('diskon_nominal', 15, 2)->default(0)->after('ppn_persen')->comment('Nominal diskon yang sudah diperhitungkan');
            $table->decimal('ppn_nominal', 15, 2)->default(0)->after('diskon_nominal')->comment('Nominal PPN yang sudah diperhitungkan');
            $table->decimal('subtotal_final', 15, 2)->default(0)->after('ppn_nominal')->comment('Subtotal final setelah diskon dan PPN');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ahsp_detail', function (Blueprint $table) {
            $table->dropColumn(['diskon_persen', 'ppn_persen', 'diskon_nominal', 'ppn_nominal', 'subtotal_final']);
        });
    }
}
