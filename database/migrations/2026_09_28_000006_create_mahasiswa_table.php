<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->uuid('id_mahasiswa')->primary();
            $table->string('nim', 100);
            $table->year('tahun_masuk')->nullable();
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
        Schema::dropIfExists('mahasiswa');
    }
};
