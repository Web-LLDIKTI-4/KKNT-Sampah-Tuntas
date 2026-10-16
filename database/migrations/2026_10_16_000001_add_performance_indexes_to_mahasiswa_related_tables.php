<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Non-unique: legacy data may contain duplicates.
    private const INDEXES = [
        'mahasiswa' => [
            'mahasiswa_kodept_location_program_index' => ['kodept', 'location_program'],
            'mahasiswa_created_at_index' => ['created_at'],
            'mahasiswa_nim_kodept_index' => ['nim', 'kodept'],
            'mahasiswa_phone_index' => ['phone'],
        ],
        'mahasiswa_lokasi' => [
            // Covering index: lookup by (id_mahasiswa, tahun) returns id_desa without row access.
            'mahasiswa_lokasi_id_mahasiswa_tahun_id_desa_index' => ['id_mahasiswa', 'tahun', 'id_desa'],
            'mahasiswa_lokasi_id_desa_tahun_index' => ['id_desa', 'tahun'],
        ],
        'dpl_mentoring' => [
            'dpl_mentoring_email_dpl_email_mahasiswa_index' => ['email_dpl', 'email_mahasiswa'],
        ],
        'capaian_kegiatan' => [
            // Existing (email, bulan) unique cannot serve bulan-first filters/sorting.
            'capaian_kegiatan_bulan_created_at_index' => ['bulan', 'created_at'],
        ],
        'ref_satuanpendidikan' => [
            'ref_satuanpendidikan_nm_lemb_index' => ['nm_lemb'],
        ],
        'dpl' => [
            'dpl_nidn_kodept_index' => ['nidn', 'kodept'],
            'dpl_phone_index' => ['phone'],
        ],
    ];

    // Old index => [columns, replacement]; the replacement serves the same left prefix.
    private const REPLACED = [
        'mahasiswa_lokasi' => [
            'mahasiswa_lokasi_id_mahasiswa_tahun_index' => [
                ['id_mahasiswa', 'tahun'],
                'mahasiswa_lokasi_id_mahasiswa_tahun_id_desa_index',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $add = array_filter(
                $indexes,
                fn (array $columns, string $name) => $this->canAdd($table, $name, $columns),
                ARRAY_FILTER_USE_BOTH
            );

            // Drop the old index only once its replacement is (or will be) present.
            $drop = [];
            foreach (self::REPLACED[$table] ?? [] as $name => [, $replacement]) {
                $covered = isset($add[$replacement])
                    || Schema::hasIndex($table, $replacement)
                    || Schema::hasIndex($table, $indexes[$replacement]);

                if ($covered && Schema::hasIndex($table, $name)) {
                    $drop[] = $name;
                }
            }

            $this->alter($table, $add, $drop);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::INDEXES, true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $drop = array_values(array_filter(
                array_keys($indexes),
                fn (string $name) => Schema::hasIndex($table, $name)
            ));

            $add = [];
            foreach (self::REPLACED[$table] ?? [] as $name => [$columns]) {
                if ($this->canAdd($table, $name, $columns)) {
                    $add[$name] = $columns;
                }
            }

            $this->alter($table, $add, $drop);
        }
    }

    private function canAdd(string $table, string $name, array $columns): bool
    {
        return Schema::hasColumns($table, $columns)
            && ! Schema::hasIndex($table, $name)
            && ! Schema::hasIndex($table, $columns);
    }

    /**
     * @param  array<string, array<int, string>>  $add
     * @param  array<int, string>  $drop
     */
    private function alter(string $table, array $add, array $drop): void
    {
        if ($add === [] && $drop === []) {
            return;
        }

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // Single online ALTER per table so reads/writes stay unblocked.
            $clauses = [];
            foreach ($add as $name => $columns) {
                $clauses[] = sprintf('ADD INDEX `%s` (`%s`)', $name, implode('`, `', $columns));
            }
            foreach ($drop as $name) {
                $clauses[] = sprintf('DROP INDEX `%s`', $name);
            }

            $sql = sprintf('ALTER TABLE `%s` %s', $table, implode(', ', $clauses));

            try {
                DB::statement($sql.', ALGORITHM=INPLACE, LOCK=NONE');
            } catch (QueryException) {
                // MyISAM / old servers reject online DDL; fall back to a plain ALTER.
                DB::statement($sql);
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($add, $drop) {
            foreach ($add as $name => $columns) {
                $blueprint->index($columns, $name);
            }
            foreach ($drop as $name) {
                $blueprint->dropIndex($name);
            }
        });
    }
};
