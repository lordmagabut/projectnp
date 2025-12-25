<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->comment('Identifier for account mapping');
            $table->string('description')->nullable()->comment('Deskripsi kegunaan akun');
            $table->unsignedBigInteger('coa_id')->nullable();
            $table->timestamps();

            $table->foreign('coa_id')->references('id')->on('coa')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_mappings');
    }
};
