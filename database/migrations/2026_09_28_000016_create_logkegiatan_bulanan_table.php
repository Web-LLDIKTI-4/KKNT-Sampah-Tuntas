<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logkegiatan_bulanan', function (Blueprint $table) {
            $table->uuid('id_logbulanan')->primary();
            $table->string('email')->nullable();
            $table->year('tahun')->nullable();
            $table->integer('bulan')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->text('tautan')->nullable();
            $table->string('nilai', 10)->nullable();
            $table->enum('status_ajuan', ['draf', 'ajuan', 'acc'])->nullable()->default('draf');
            $table->text('hasil_verifikasi')->nullable();
            $table->string('verifikator', 100)->nullable();
            $table->timestamps();
            $table->index(['email', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logkegiatan_bulanan');
    }
};
