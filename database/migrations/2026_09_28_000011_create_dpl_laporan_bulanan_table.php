<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpl_laporan_bulanan', function (Blueprint $table) {
            $table->uuid('id_laporan')->primary();
            $table->string('email')->nullable()->index();
            $table->year('tahun')->nullable();
            $table->integer('bulan')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->string('tautan')->nullable();
            $table->enum('status_ajuan', ['draf', 'ajuan', 'acc'])->nullable()->default('draf');
            $table->text('hasil_verifikasi')->nullable();
            $table->string('verifikator', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpl_laporan_bulanan');
    }
};
