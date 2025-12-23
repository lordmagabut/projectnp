<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyek_id')->nullable();
            $table->unsignedBigInteger('penawaran_id')->nullable();
            $table->string('no_so')->unique();
            $table->date('tanggal')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
};
