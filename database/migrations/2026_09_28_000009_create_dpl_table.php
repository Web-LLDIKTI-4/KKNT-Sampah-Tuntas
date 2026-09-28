<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpl', function (Blueprint $table) {
            $table->uuid('id_dpl')->primary();
            $table->string('nidn', 100);
            $table->string('nama')->nullable();
            $table->string('email')->nullable()->index();
            $table->uuid('location_program')->nullable()->index();
            $table->string('prodi', 100)->nullable();
            $table->string('kodept', 10)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpl');
    }
};
