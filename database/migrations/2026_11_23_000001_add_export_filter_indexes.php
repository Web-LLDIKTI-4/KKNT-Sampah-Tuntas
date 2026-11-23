<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Existing indexes on these tables start with email, so they cannot serve the
    // month filters used by admin/kepala/pemda exports (no email filter).
    private const INDEXES = [
        'logkegiatan' => [
            // created_at second: matches the leading ORDER BY of the log harian export.
            'logkegiatan_tanggal_created_at_index' => ['tanggal', 'created_at'],
        ],
        'kehadiran' => [
            'kehadiran_tanggal_index' => ['tanggal'],
        ],
        'logkegiatan_bulanan' => [
            'logkegiatan_bulanan_tahun_bulan_index' => ['tahun', 'bulan'],
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
                fn (array $columns, string $name) => Schema::hasColumns($table, $columns)
                    && ! Schema::hasIndex($table, $name)
                    && ! Schema::hasIndex($table, $columns),
                ARRAY_FILTER_USE_BOTH
            );

            $this->alter($table, $add, []);
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

            $this->alter($table, [], $drop);
        }
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
