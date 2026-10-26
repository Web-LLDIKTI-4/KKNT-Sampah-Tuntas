<?php

namespace Tests\Feature\Profile;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FormLokasiCountTest extends TestCase
{
    use RefreshDatabase;

    private function expectedText(): string
    {
        $kecamatan = Kecamatan::count();
        $desa = Desa::whereIn('id_kecamatan', Kecamatan::select('id_kecamatan'))->count();

        return "{$kecamatan} kecamatan, {$desa} desa dimuat";
    }

    public function test_count_text_matches_db_including_kecamatan_without_desa(): void
    {
        $this->loginAs('mahasiswa');
        $kecamatan = Kecamatan::factory()->create();
        Desa::factory()->count(3)->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
        Kecamatan::factory()->count(2)->create();

        // loginAs seeds 1 kecamatan + 1 desa; here +3 kecamatan (2 empty), +3 desa
        $this->assertSame('4 kecamatan, 4 desa dimuat', $this->expectedText());

        $this->get('mhsprofile/formlokasi')
            ->assertOk()
            ->assertSee('4 kecamatan, 4 desa dimuat')
            ->assertSee('select2-lokasi-desa');
    }

    public function test_count_text_shows_zero_desa_when_only_empty_kecamatan(): void
    {
        $this->loginAs('mahasiswa');
        Desa::query()->delete();
        Kecamatan::factory()->count(2)->create();

        $this->get('mhsprofile/formlokasi')
            ->assertOk()
            ->assertSee($this->expectedText())
            ->assertSee('kecamatan, 0 desa dimuat');
    }

    public function test_query_count_does_not_grow_with_kecamatan_count(): void
    {
        $this->loginAs('mahasiswa');

        $countQueries = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('mhsprofile/formlokasi')->assertOk();
            $total = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $total;
        };

        // Warm-up: user->mahasiswa relation is cached on the acting user after first request
        $countQueries();
        $baseline = $countQueries();

        foreach (Kecamatan::factory()->count(5)->create() as $kecamatan) {
            Desa::factory()->count(2)->create(['id_kecamatan' => $kecamatan->id_kecamatan]);
        }

        $this->assertSame($baseline, $countQueries(), 'Query count grows with data (N+1)');
        $this->get('mhsprofile/formlokasi')->assertSee($this->expectedText());
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('mhsprofile/formlokasi')->assertRedirect();
    }
}
