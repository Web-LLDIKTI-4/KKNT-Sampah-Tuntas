<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOKASI_INDEX = 'mahasiswa_lokasi_id_mahasiswa_tahun_index';

    private const TANGGAL_INDEX = 'pendataan_pemilahan_sampah_tanggal_index';

    public function up(): void
    {
        // Non-unique: legacy data may contain duplicate (id_mahasiswa, tahun) rows.
        if (! Schema::hasIndex('mahasiswa_lokasi', self::LOKASI_INDEX)) {
            Schema::table('mahasiswa_lokasi', function (Blueprint $table) {
                $table->index(['id_mahasiswa', 'tahun'], self::LOKASI_INDEX);
            });
        }

        // Existing (email, tanggal) index cannot serve tanggal-only range filters.
        if (! Schema::hasIndex('pendataan_pemilahan_sampah', self::TANGGAL_INDEX)) {
            Schema::table('pendataan_pemilahan_sampah', function (Blueprint $table) {
                $table->index('tanggal', self::TANGGAL_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('pendataan_pemilahan_sampah', self::TANGGAL_INDEX)) {
            Schema::table('pendataan_pemilahan_sampah', function (Blueprint $table) {
                $table->dropIndex(self::TANGGAL_INDEX);
            });
        }

        if (Schema::hasIndex('mahasiswa_lokasi', self::LOKASI_INDEX)) {
            Schema::table('mahasiswa_lokasi', function (Blueprint $table) {
                $table->dropIndex(self::LOKASI_INDEX);
            });
        }
    }
};
