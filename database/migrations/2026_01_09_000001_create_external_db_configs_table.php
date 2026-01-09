<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('external_db_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('External Database');
            $table->string('host');
            $table->string('port')->default('3306');
            $table->string('database');
            $table->string('username');
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('external_db_configs');
    }
};
