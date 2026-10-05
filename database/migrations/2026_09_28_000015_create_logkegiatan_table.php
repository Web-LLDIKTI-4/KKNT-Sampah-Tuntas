<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logkegiatan', function (Blueprint $table) {
            $table->uuid('id_log')->primary();
            $table->string('email');
            $table->date('tanggal');
            $table->string('nama_kepala_keluarga', 150);
            $table->string('alamat_rumah');
            $table->string('rt', 5);
            $table->string('rw', 5);
            $table->boolean('memilah');
            $table->decimal('organik_kg', 10, 2);
            $table->decimal('anorganik_kg', 10, 2);
            $table->decimal('residu_kg', 10, 2);
            $table->timestamps();
            $table->index(['email', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logkegiatan');
    }
};
