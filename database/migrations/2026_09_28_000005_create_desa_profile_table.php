<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desa_profile', function (Blueprint $table) {
            $table->uuid('id_profile')->primary();
            $table->uuid('id_desa')->nullable()->index();
            $table->year('tahun')->nullable();
            $table->text('potensi')->nullable();
            $table->text('masalah')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desa_profile');
    }
};
