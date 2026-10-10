<?php

namespace Tests\Feature;

use Database\Seeders\PetaSebaranDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PetaSebaranDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    private function counts(): array
    {
        return [
            'kecamatan' => DB::table('kecamatan')->count(),
            'desa' => DB::table('desa')->count(),
            'mahasiswa' => DB::table('mahasiswa')->count(),
            'lokasi' => DB::table('mahasiswa_lokasi')->count(),
            'pendataan' => DB::table('pendataan_pemilahan_sampah')->count(),
        ];
    }

    private function kecamatan(string $nama, string $createdAt = '2026-01-01 00:00:00'): string
    {
        $id = (string) Str::uuid();
        DB::table('kecamatan')->insert(['id_kecamatan' => $id, 'kecamatan' => $nama, 'created_at' => $createdAt, 'updated_at' => $createdAt]);

        return $id;
    }

    private function desa(string $idKecamatan, string $nama, float $lat = -7.0, float $lng = 107.6, string $createdAt = '2026-01-01 00:00:00'): string
    {
        $id = (string) Str::uuid();
        DB::table('desa')->insert([
            'id_desa' => $id, 'id_kecamatan' => $idKecamatan, 'desa' => $nama,
            'latitude' => $lat, 'longitude' => $lng, 'created_at' => $createdAt, 'updated_at' => $createdAt,
        ]);

        return $id;
    }

    private function lokasi(string $idDesa, ?string $idMahasiswa = null, int $tahun = 2026): void
    {
        DB::table('mahasiswa_lokasi')->insert([
            'id_lokasi' => (string) Str::uuid(), 'tahun' => $tahun, 'id_mahasiswa' => $idMahasiswa ?? (string) Str::uuid(),
            'id_desa' => $idDesa, 'user_in_up' => 'test', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_running_seeder_twice_does_not_duplicate_anything(): void
    {
        $this->seed(PetaSebaranDemoSeeder::class);
        $first = $this->counts();
        $this->seed(PetaSebaranDemoSeeder::class);

        $this->assertSame($first, $this->counts());
        $this->assertGreaterThan(0, $first['desa']);
        $this->assertSame(0, count(DB::select('select 1 from desa group by desa, latitude, longitude having count(*) > 1')));
    }

    public function test_seeder_cleans_legacy_demo_rows_with_non_uuid5_ids(): void
    {
        $legacyKecamatan = $this->kecamatan('Baleendah (Demo)');
        $this->desa($legacyKecamatan, 'Andir (Demo)', -6.9951, 107.6312);

        $this->seed(PetaSebaranDemoSeeder::class);

        $this->assertSame(1, DB::table('kecamatan')->where('kecamatan', 'Baleendah (Demo)')->count());
        $this->assertSame(1, DB::table('desa')->where('desa', 'Andir (Demo)')->count());
    }

    public function test_seeder_reuses_legacy_demo_desa_still_referenced_by_real_data(): void
    {
        $legacyKecamatan = $this->kecamatan('Baleendah (Demo)');
        $legacyDesa = $this->desa($legacyKecamatan, 'Andir (Demo)', -6.9951, 107.6312);
        $this->lokasi($legacyDesa);

        $this->seed(PetaSebaranDemoSeeder::class);
        $this->seed(PetaSebaranDemoSeeder::class);

        $this->assertSame([$legacyDesa], DB::table('desa')->where('desa', 'Andir (Demo)')->pluck('id_desa')->all());
        $this->assertSame([$legacyKecamatan], DB::table('kecamatan')->where('kecamatan', 'Baleendah (Demo)')->pluck('id_kecamatan')->all());
    }

    public function test_dedupe_command_merges_demo_duplicates_and_keeps_real_desa(): void
    {
        $kecA = $this->kecamatan('Soreang (Demo)', '2026-01-01 00:00:00');
        $kecB = $this->kecamatan('Soreang (Demo)', '2026-01-02 00:00:00');
        $empty = $this->desa($kecA, 'Sadu (Demo)', -7.0412, 107.5101, '2026-01-01 00:00:00');
        $canonical = $this->desa($kecB, 'Sadu (Demo)', -7.0412, 107.5101, '2026-01-02 00:00:00');
        $other = $this->desa($kecB, 'Sadu (Demo)', -7.0412, 107.5101, '2026-01-03 00:00:00');
        $mahasiswa = (string) Str::uuid();
        $this->lokasi($canonical, $mahasiswa);
        $this->lokasi($canonical);
        $this->lokasi($other, $mahasiswa);
        $this->lokasi($other);

        $realKec = $this->kecamatan('Soreang');
        $this->desa($realKec, 'Sadu');
        $this->desa($realKec, 'Sadu');

        $this->artisan('demo:dedupe-desa', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(5, DB::table('desa')->count());
        $this->assertSame(3, DB::table('kecamatan')->count());

        $this->artisan('demo:dedupe-desa')->assertSuccessful();

        $this->assertSame([$canonical], DB::table('desa')->where('desa', 'Sadu (Demo)')->pluck('id_desa')->all());
        $this->assertSame([$kecB], DB::table('kecamatan')->where('kecamatan', 'Soreang (Demo)')->pluck('id_kecamatan')->all());
        $this->assertSame(2, DB::table('desa')->where('desa', 'Sadu')->count());
        // Identical (same mahasiswa & tahun) row dropped, the other moved
        $this->assertSame(3, DB::table('mahasiswa_lokasi')->where('id_desa', $canonical)->count());
        $this->assertSame(0, DB::table('mahasiswa_lokasi')->whereIn('id_desa', [$empty, $other])->count());
    }
}
