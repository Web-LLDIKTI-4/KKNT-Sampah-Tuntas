<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kehadiran', function (Blueprint $table) {
            $table->uuid('id_kehadiran')->primary();
            $table->string('email')->nullable();
            $table->date('tanggal')->nullable();
            $table->string('status_kehadiran', 50)->nullable();
            $table->dateTime('waktu_masuk')->nullable();
            $table->decimal('latitude_datang', 10, 7)->nullable();
            $table->decimal('longitude_datang', 10, 7)->nullable();
            $table->dateTime('waktu_pulang')->nullable();
            $table->decimal('latitude_pulang', 10, 7)->nullable();
            $table->decimal('longitude_pulang', 10, 7)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index(['email', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kehadiran');
    }
};
