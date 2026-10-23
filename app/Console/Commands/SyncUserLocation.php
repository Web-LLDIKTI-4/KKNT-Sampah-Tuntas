<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Samakan users.location_program dengan sumbernya (mahasiswa/dpl) berdasarkan email.
 */
class SyncUserLocation extends Command
{
    protected $signature = 'users:sync-location
        {--dry-run : Tampilkan perbedaan saja (default bila --force tidak diberikan)}
        {--force : Jalankan update}
        {--chunk=500 : Jumlah user per batch}';

    protected $description = 'Samakan users.location_program dengan mahasiswa/dpl.location_program (sumber kebenaran)';

    /** role => source table */
    protected const SOURCES = ['mahasiswa' => 'mahasiswa', 'dpl' => 'dpl'];

    public function handle(): int
    {
        $apply = (bool) $this->option('force') && ! $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $diffs = [];
        $skipped = [];
        $ambiguous = [];
        $inSync = 0;

        DB::table('users')
            ->select(['id', 'email', 'role', 'location_program'])
            ->whereIn('role', array_keys(self::SOURCES))
            ->chunkById($chunk, function (Collection $users) use (&$diffs, &$skipped, &$ambiguous, &$inSync) {
                $emails = $users->pluck('email')->all();
                $sources = [];
                foreach (self::SOURCES as $role => $table) {
                    $sources[$role] = DB::table($table)
                        ->whereIn('email', $emails)
                        ->get(['email', 'location_program'])
                        ->groupBy(fn ($row) => $this->key($row->email));
                }

                foreach ($users as $user) {
                    $key = $this->key($user->email);
                    $own = $sources[$user->role]->get($key, collect());
                    $otherRoles = array_filter(
                        array_keys(self::SOURCES),
                        fn ($role) => $role !== $user->role && $sources[$role]->has($key)
                    );

                    if ($otherRoles) {
                        $ambiguous[] = [$user->email, $user->role, 'email juga ada di tabel '.implode(', ', $otherRoles)];
                        continue;
                    }
                    if ($own->isEmpty()) {
                        $skipped[] = [$user->email, $user->role, 'tidak ada baris sumber'];
                        continue;
                    }

                    $values = $own->pluck('location_program')->unique()->values();
                    if ($values->count() > 1) {
                        $ambiguous[] = [$user->email, $user->role, $own->count().' baris sumber dengan lokasi berbeda'];
                        continue;
                    }

                    $new = $values->first();
                    if ($new === null) {
                        $skipped[] = [$user->email, $user->role, 'location_program sumber NULL'];
                        continue;
                    }
                    if ($new === $user->location_program) {
                        $inSync++;
                        continue;
                    }

                    $diffs[] = ['id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'old' => $user->location_program, 'new' => $new];
                }
            });

        $this->renderReport($diffs, $skipped, $ambiguous);

        $updated = 0;
        $conflicts = [];
        if ($apply && $diffs) {
            try {
                DB::transaction(function () use ($diffs, &$updated, &$conflicts) {
                    foreach ($diffs as $row) {
                        // Update only if the value has not changed since it was read.
                        $affected = DB::table('users')
                            ->where('id', $row['id'])
                            ->where(fn ($q) => $row['old'] === null
                                ? $q->whereNull('location_program')
                                : $q->where('location_program', $row['old']))
                            ->update(['location_program' => $row['new']]);

                        if ($affected) {
                            $updated++;
                        } else {
                            $conflicts[] = [$row['email'], $row['role'], 'berubah selama proses, dilewati'];
                        }
                    }
                });
            } catch (Throwable $e) {
                $this->error('Gagal, semua perubahan di-rollback: '.$e->getMessage());

                return self::FAILURE;
            }

            if ($conflicts) {
                $this->warn('Dilewati karena berubah selama proses:');
                $this->table(['Email', 'Role', 'Alasan'], $conflicts);
            }
        }

        $this->table(['Item', 'Jumlah'], [
            ['Sudah sinkron', $inSync],
            ['Perlu update', count($diffs)],
            ['Dilewati (tanpa sumber / sumber NULL)', count($skipped)],
            ['Ambigu (tidak di-update)', count($ambiguous)],
            ['Ter-update', $updated],
        ]);

        $this->info($apply
            ? 'Selesai.'
            : 'Dry run: tidak ada perubahan disimpan. Jalankan dengan --force untuk update.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $diffs
     * @param  array<int, array<int, string>>  $skipped
     * @param  array<int, array<int, string>>  $ambiguous
     */
    protected function renderReport(array $diffs, array $skipped, array $ambiguous): void
    {
        if ($diffs) {
            $this->table(
                ['Email', 'Role', 'Lama', 'Baru'],
                array_map(fn ($r) => [$r['email'], $r['role'], $r['old'] ?? 'NULL', $r['new']], $diffs)
            );
        } else {
            $this->info('Tidak ada perbedaan location_program.');
        }

        if ($skipped) {
            $this->line('Dilewati:');
            $this->table(['Email', 'Role', 'Alasan'], $skipped);
        }

        if ($ambiguous) {
            $this->warn('Ambigu, tidak di-update:');
            $this->table(['Email', 'Role', 'Alasan'], $ambiguous);
        }
    }

    // MySQL default collation compares email case-insensitively; mirror that in PHP.
    protected function key(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}
