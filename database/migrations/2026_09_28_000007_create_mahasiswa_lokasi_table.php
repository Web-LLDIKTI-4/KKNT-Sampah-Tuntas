<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mahasiswa_lokasi', function (Blueprint $table) {
            $table->uuid('id_lokasi')->primary();
            $table->year('tahun')->nullable();
            $table->uuid('id_mahasiswa')->nullable()->index();
            $table->uuid('id_desa')->nullable()->index();
            $table->string('user_in_up', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa_lokasi');
    }
};
