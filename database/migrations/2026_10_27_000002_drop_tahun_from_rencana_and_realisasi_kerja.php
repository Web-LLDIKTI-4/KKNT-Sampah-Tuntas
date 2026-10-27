<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['rencana_kerja', 'realisasi_kerja'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // FK kodept memakai index komposit; sediakan pengganti dulu agar drop tidak gagal (MySQL 1553)
                $table->index('kodept');
                $table->dropIndex(['kodept', 'tahun']);
                $table->dropIndex(['tahun']);
                $table->dropColumn('tahun');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Nilai tahun lama tidak bisa dipulihkan; baris lama diisi tahun berjalan
                $table->unsignedSmallInteger('tahun')->default((int) date('Y'))->after('judul');
                $table->index('tahun');
                $table->index(['kodept', 'tahun']);
                $table->dropIndex(['kodept']);
            });
        }
    }
};
