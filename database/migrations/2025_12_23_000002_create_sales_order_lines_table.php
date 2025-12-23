<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->string('description');
            $table->string('line_type')->nullable(); // 'material' | 'jasa' | 'summary'
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_order_lines');
    }
};
