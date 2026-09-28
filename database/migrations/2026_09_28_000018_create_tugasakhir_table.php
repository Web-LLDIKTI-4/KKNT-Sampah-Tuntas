<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugasakhir', function (Blueprint $table) {
            $table->uuid('id_tugasakhir')->primary();
            $table->year('tahun')->nullable();
            $table->string('email')->nullable()->index();
            $table->text('tautan')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('status_ajuan', ['draf', 'ajuan', 'acc'])->nullable()->default('draf');
            $table->integer('nilai_dpl')->nullable();
            $table->string('email_dpl', 200)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugasakhir');
    }
};
