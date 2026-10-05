<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Satu data sampah per ketua (email) per bulan, bukan per kelurahan
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sampah', function (Blueprint $table) {
            $table->unique(['email', 'bulan']);
            $table->dropUnique(['id_desa', 'bulan']);
        });
    }

    // Gagal bila sudah ada >1 data per kelurahan per bulan
    public function down(): void
    {
        Schema::table('kpi_sampah', function (Blueprint $table) {
            $table->unique(['id_desa', 'bulan']);
            $table->dropUnique(['email', 'bulan']);
        });
    }
};
