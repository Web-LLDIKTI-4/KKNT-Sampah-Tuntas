<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Capaian KPI kini diukur dari persentase pengurangan sampah; target & realisasi per kegiatan tidak dipakai lagi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->dropIndex(['id_target']);
            $table->dropColumn(['id_target', 'realisasi', 'satuan']);
        });

        Schema::dropIfExists('kpi_target');
    }

    // Struktur dibuat ulang; data target & realisasi yang sudah dihapus tidak kembali
    public function down(): void
    {
        Schema::create('kpi_target', function (Blueprint $table) {
            $table->uuid('id_target')->primary();
            $table->uuid('id_kpi')->nullable()->index();
            $table->string('kegiatan', 200)->nullable();
            $table->decimal('target', 12, 2)->nullable();
            $table->string('satuan', 50)->nullable();
            $table->timestamps();
        });

        Schema::table('kpi_capaian', function (Blueprint $table) {
            $table->uuid('id_target')->nullable()->index()->after('id_kpi');
            $table->decimal('realisasi', 12, 2)->nullable()->after('id_target');
            $table->string('satuan', 50)->nullable()->after('realisasi');
        });
    }
};
