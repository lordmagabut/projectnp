<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event', 100);              // login, logout, create_po, update_faktur, dll
            $table->text('description')->nullable();   // detail bebas
            $table->string('ip_address', 45)->nullable();
            $table->string('device_name', 150)->nullable(); // contoh: "Windows 11 · Chrome 120"
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'event', 'created_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('activity_logs');
    }
};
