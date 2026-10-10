<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ADR 2026-10-10: "KPI" split into kategori kegiatan, capaian kegiatan, and pengurangan sampah.
return new class extends Migration
{
    private const TABLES = [
        'kpi' => 'kategori_kegiatan',
        'kpi_capaian' => 'capaian_kegiatan',
        'kpi_sampah' => 'pengurangan_sampah',
    ];

    // Keyed by the new table name; applied after the table rename.
    private const COLUMNS = [
        'kategori_kegiatan' => ['id_kpi' => 'id_kategori', 'nama_kpi' => 'nama_kategori'],
        'capaian_kegiatan' => ['id_kpi' => 'id_kategori'],
        'logkegiatan' => ['id_kpi' => 'id_kategori'],
    ];

    private const INDEXES = [
        'capaian_kegiatan' => [
            'kpi_capaian_id_kpi_index' => 'capaian_kegiatan_id_kategori_index',
            'kpi_capaian_id_pjdesa_index' => 'capaian_kegiatan_id_pjdesa_index',
            'kpi_capaian_email_index' => 'capaian_kegiatan_email_index',
            'kpi_capaian_email_bulan_unique' => 'capaian_kegiatan_email_bulan_unique',
        ],
        'pengurangan_sampah' => [
            'kpi_sampah_id_pjdesa_index' => 'pengurangan_sampah_id_pjdesa_index',
            'kpi_sampah_email_index' => 'pengurangan_sampah_email_index',
            'kpi_sampah_id_desa_index' => 'pengurangan_sampah_id_desa_index',
            'kpi_sampah_email_bulan_unique' => 'pengurangan_sampah_email_bulan_unique',
            'kpi_sampah_bulan_index' => 'pengurangan_sampah_bulan_index',
        ],
        'logkegiatan' => [
            'logkegiatan_id_kpi_index' => 'logkegiatan_id_kategori_index',
        ],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $from => $to) {
            $this->renameTable($from, $to);
        }

        foreach (self::COLUMNS as $table => $columns) {
            $this->renameColumns($table, $columns);
        }

        foreach (self::INDEXES as $table => $indexes) {
            $this->renameIndexes($table, $indexes);
        }

        $this->assertSchema(self::TABLES, self::COLUMNS);
    }

    public function down(): void
    {
        foreach (array_reverse(self::INDEXES, true) as $table => $indexes) {
            $this->renameIndexes($table, array_flip($indexes));
        }

        foreach (array_reverse(self::COLUMNS, true) as $table => $columns) {
            $this->renameColumns($table, array_flip($columns));
        }

        foreach (array_reverse(self::TABLES, true) as $from => $to) {
            $this->renameTable($to, $from);
        }

        $legacyColumns = [];
        foreach (self::COLUMNS as $table => $columns) {
            $legacyColumns[array_search($table, self::TABLES, true) ?: $table] = array_flip($columns);
        }
        $this->assertSchema(array_flip(self::TABLES), $legacyColumns);
    }

    // Skips when already renamed; refuses to guess when both names exist.
    private function renameTable(string $from, string $to): void
    {
        if (Schema::hasTable($from) && Schema::hasTable($to)) {
            throw new \RuntimeException("Tabel '{$from}' dan '{$to}' sama-sama ada; gabungkan/hapus salah satu secara manual sebelum migrate.");
        }

        if (Schema::hasTable($from)) {
            Schema::rename($from, $to);
        }
    }

    private function renameColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $from => $to) {
            if (Schema::hasColumn($table, $from) && Schema::hasColumn($table, $to)) {
                throw new \RuntimeException("Kolom '{$table}.{$from}' dan '{$table}.{$to}' sama-sama ada; selesaikan manual sebelum migrate.");
            }

            if (Schema::hasColumn($table, $from)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($from, $to));
            }
        }
    }

    // $tables: old => expected name; $columns: expected table => [old => expected column].
    private function assertSchema(array $tables, array $columns): void
    {
        foreach ($tables as $old => $expected) {
            if (! Schema::hasTable($expected) || Schema::hasTable($old)) {
                throw new \RuntimeException("Validasi migrasi gagal: tabel '{$expected}' harus ada dan '{$old}' tidak boleh ada.");
            }
        }

        foreach ($columns as $table => $pairs) {
            foreach ($pairs as $old => $expected) {
                if (! Schema::hasColumn($table, $expected) || Schema::hasColumn($table, $old)) {
                    throw new \RuntimeException("Validasi migrasi gagal: kolom '{$table}.{$expected}' harus ada dan '{$table}.{$old}' tidak boleh ada.");
                }
            }
        }
    }

    // Guarded: legacy production tables may lack these index names.
    private function renameIndexes(string $table, array $indexes): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($indexes as $from => $to) {
            if (Schema::hasIndex($table, $from) && ! Schema::hasIndex($table, $to)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameIndex($from, $to));
            }
        }
    }
};
