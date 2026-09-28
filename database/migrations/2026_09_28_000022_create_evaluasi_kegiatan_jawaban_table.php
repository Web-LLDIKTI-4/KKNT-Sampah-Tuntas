<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_kegiatan_jawaban', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_evaluasi')->nullable()->index();
            $table->text('jawaban')->nullable();
            $table->year('tahun')->nullable();
            $table->string('kodept', 10)->nullable()->index();
            $table->string('user', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_kegiatan_jawaban');
    }
};
