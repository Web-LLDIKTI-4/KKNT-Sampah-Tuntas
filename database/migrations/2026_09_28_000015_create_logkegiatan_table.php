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
            $table->string('email')->nullable();
            $table->date('tanggal')->nullable();
            $table->longText('deskripsi')->nullable();
            $table->string('volume', 100)->nullable();
            $table->text('satuan')->nullable();
            $table->uuid('id_kpi')->nullable()->index();
            $table->string('tautan')->nullable();
            $table->timestamps();
            $table->index(['email', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logkegiatan');
    }
};
