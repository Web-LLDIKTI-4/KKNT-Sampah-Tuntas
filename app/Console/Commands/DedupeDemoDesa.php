<?php

namespace App\Console\Commands;

use App\Models\PenguranganSampah;
use App\Services\PetaSebaranService;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Gabungkan kecamatan/desa demo ganda (nama berakhiran " (Demo)"); wilayah asli tidak disentuh.
 */
class DedupeDemoDesa extends Command
{
    use ConfirmableTrait;

    protected $signature = 'demo:dedupe-desa {--dry-run : Hitung saja, semua perubahan di-rollback} {--force : Jalankan di production tanpa konfirmasi}';

    protected $description = 'Gabungkan kecamatan & desa demo ganda dan pindahkan referensinya ke salinan kanonik';

    protected const SUFFIX = ' (Demo)';

    /** @var array<string, int> */
    protected array $stats = [];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if (! $dryRun && ! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $this->stats = ['kecamatan_dihapus' => 0, 'desa_dihapus' => 0, 'referensi_dipindah' => 0, 'referensi_identik_dihapus' => 0];

        DB::beginTransaction();
        try {
            $this->mergeKecamatan();
            $this->mergeDesa();
            $dryRun ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if (! $dryRun && array_sum($this->stats) > 0) {
            PetaSebaranService::flushCache();
            PenguranganSampah::forgetPublicCache();
        }

        $this->table(['Item', 'Jumlah'], collect($this->stats)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info($dryRun ? 'Dry run: tidak ada perubahan disimpan.' : 'Selesai.');

        return self::SUCCESS;
    }

    protected function mergeKecamatan(): void
    {
        $rows = DB::table('kecamatan')->where('kecamatan', 'like', '%'.self::SUFFIX)->get();
        $refTables = $this->referencingTables('id_kecamatan', 'kecamatan');

        foreach ($rows->groupBy('kecamatan')->filter(fn (Collection $g) => $g->count() > 1) as $nama => $group) {
            // Score = desa count + references of those desa, so the copy holding real relations wins
            $canonical = $this->pickCanonical($group, 'id_kecamatan', function (string $id) {
                $desaIds = DB::table('desa')->where('id_kecamatan', $id)->pluck('id_desa')->all();

                return count($desaIds) + $this->countReferences('id_desa', $desaIds, 'desa');
            });

            foreach ($group->pluck('id_kecamatan')->reject(fn ($id) => $id === $canonical) as $duplicate) {
                // Desa are moved as-is; identical desa are merged later with their references
                $this->moveReferences('id_kecamatan', $duplicate, $canonical, $refTables, false);
                DB::table('kecamatan')->where('id_kecamatan', $duplicate)->delete();
                $this->stats['kecamatan_dihapus']++;
                $this->line("Kecamatan {$nama}: {$duplicate} -> {$canonical}");
            }
        }
    }

    protected function mergeDesa(): void
    {
        $rows = DB::table('desa')->where('desa', 'like', '%'.self::SUFFIX)->get();
        $refTables = $this->referencingTables('id_desa', 'desa');

        $groups = $rows->groupBy(fn ($r) => implode('|', [$r->desa, $r->id_kecamatan, (string) $r->latitude, (string) $r->longitude]))
            ->filter(fn (Collection $g) => $g->count() > 1);

        foreach ($groups as $group) {
            $canonical = $this->pickCanonical($group, 'id_desa', fn (string $id) => $this->countReferences('id_desa', [$id], 'desa'));

            foreach ($group->pluck('id_desa')->reject(fn ($id) => $id === $canonical) as $duplicate) {
                $this->moveReferences('id_desa', $duplicate, $canonical, $refTables);
                DB::table('desa')->where('id_desa', $duplicate)->delete();
                $this->stats['desa_dihapus']++;
                $this->line("Desa {$group->first()->desa}: {$duplicate} -> {$canonical}");
            }
        }
    }

    /**
     * Highest score wins; ties go to the oldest row, then the smallest id.
     */
    protected function pickCanonical(Collection $group, string $key, callable $score): string
    {
        return $group->map(fn ($r) => ['id' => $r->{$key}, 'score' => $score($r->{$key}), 'created' => (string) $r->created_at])
            ->sort(fn ($a, $b) => [$b['score'], $a['created'], $a['id']] <=> [$a['score'], $b['created'], $b['id']])
            ->first()['id'];
    }

    /**
     * @return list<string>
     */
    protected function referencingTables(string $column, string $ownTable): array
    {
        return collect(Schema::getTableListing(schemaQualified: false))
            ->reject(fn ($t) => $t === $ownTable)
            ->filter(fn ($t) => Schema::hasColumn($t, $column))
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $ids
     */
    protected function countReferences(string $column, array $ids, string $ownTable): int
    {
        if (! $ids) {
            return 0;
        }

        return collect($this->referencingTables($column, $ownTable))
            ->sum(fn ($t) => DB::table($t)->whereIn($column, $ids)->count());
    }

    /**
     * Move child rows to the canonical parent; a moved row identical to an existing canonical row is deleted instead.
     *
     * @param  list<string>  $tables
     */
    protected function moveReferences(string $column, string $from, string $to, array $tables, bool $dropIdentical = true): void
    {
        foreach ($tables as $table) {
            $primary = collect(Schema::getIndexes($table))->firstWhere('primary', true)['columns'] ?? [];
            $ignore = [...$primary, $column, 'created_at', 'updated_at'];
            $fingerprint = fn ($row) => md5(json_encode(collect((array) $row)->except($ignore)->sortKeys()->all()));

            $existing = $dropIdentical ? DB::table($table)->where($column, $to)->get()->map($fingerprint)->flip() : collect();
            foreach (DB::table($table)->where($column, $from)->get() as $row) {
                $where = $primary ? collect((array) $row)->only($primary)->all() : (array) $row;
                if ($existing->has($fingerprint($row))) {
                    DB::table($table)->where($where)->delete();
                    $this->stats['referensi_identik_dihapus']++;

                    continue;
                }
                DB::table($table)->where($where)->update([$column => $to]);
                $this->stats['referensi_dipindah']++;
            }
        }
    }
}
