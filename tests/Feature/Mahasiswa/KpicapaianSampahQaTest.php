<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Models\Pjdesa;
use App\Services\KpiSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KpicapaianSampahQaTest extends TestCase
{
    use RefreshDatabase;

    private Kpi $kpi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kpi = Kpi::factory()->create();
    }

    private function loginKetua(bool $denganDesa = true)
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        if ($denganDesa) {
            Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        }

        return $user;
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'id_kpi' => $this->kpi->id_kpi,
            'bulan' => '2026-03-15',
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
            'jml_rw' => 1,
            'jml_penduduk' => 10,
            'jml_rumah' => 3,
            'jml_rumah_memilah' => 1,
            'timbulan' => 300,
            'organik_sumber' => 30,
            'organik_metode_unit' => 0,
            'organik_dlh' => 20,
            'anorganik_sumber' => 25.5,
            'anorganik_metode_unit' => 0,
        ];
    }

    private function sisa(string $email, string $bulan, ?string $idDesa = null): Kpisampah
    {
        return Kpisampah::create(['email' => $email, 'bulan' => $bulan, 'id_desa' => $idDesa ?? Desa::factory()->create()->id_desa,
            'jml_rw' => 0, 'jml_penduduk' => 0, 'jml_rumah' => 0, 'jml_rumah_memilah' => 0, 'timbulan' => 1,
            'organik_sumber' => 0, 'organik_dlh' => 0, 'anorganik_sumber' => 0, 'pengurangan' => 0, 'belum_terkelola' => 1]);
    }

    public function test_insert_derived_values_rounding_and_timbulan_zero(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload())->assertJson(['success' => true]);
        $s = Kpisampah::firstOrFail();
        $this->assertSame(75.5, $s->pengurangan);
        $this->assertSame(224.5, $s->belum_terkelola);
        $this->assertEqualsWithDelta(25.17, $s->persen_pengurangan, 0.01);
        $this->assertEqualsWithDelta(33.33, $s->persen_ketaatan, 0.01);
        $this->assertSame(Kpicapaian::firstOrFail()->bulan, $s->bulan->toDateString());

        // Timbulan & rumah 0 → tidak error (bagi nol)
        $this->put('kpicapaian/insert', $this->payload([
            'bulan' => '2026-04-02', 'jml_rumah' => 0, 'jml_rumah_memilah' => 0,
            'timbulan' => 0, 'organik_sumber' => 0, 'organik_dlh' => 0, 'anorganik_sumber' => 0,
        ]))->assertJson(['success' => true]);
        $this->assertDatabaseCount('kpi_sampah', 2);
    }

    public function test_insert_reuses_leftover_row_in_same_month_without_unique_clash(): void
    {
        $user = $this->loginKetua();
        $sisa = $this->sisa($user->email, '2026-03-01');

        $this->put('kpicapaian/insert', $this->payload())->assertJson(['success' => true]);

        $this->assertDatabaseCount('kpi_sampah', 1);
        $s = Kpisampah::firstOrFail();
        $this->assertSame($sisa->id_sampah, $s->id_sampah);
        $this->assertSame($sisa->id_desa, $s->id_desa);
        $this->assertSame(300.0, $s->timbulan);
    }

    public function test_update_same_month_updates_row_in_place(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload())->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();
        $id = Kpisampah::firstOrFail()->id_sampah;

        $this->put('kpicapaian/update', $this->payload(['id_capaian' => $capaian->id_capaian, 'bulan' => '2026-03-28', 'keterangan' => 'Revisi']))
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('kpi_sampah', 1);
        $this->assertSame($id, Kpisampah::firstOrFail()->id_sampah);
        $this->assertSame('Revisi', Kpisampah::firstOrFail()->keterangan);
    }

    public function test_update_to_month_of_other_capaian_rejected_and_sampah_untouched(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload())->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(['bulan' => '2026-04-05', 'timbulan' => 500]))->assertJson(['success' => true]);
        $maret = Kpicapaian::where('bulan', '2026-03-01')->firstOrFail();

        $this->put('kpicapaian/update', $this->payload(['id_capaian' => $maret->id_capaian, 'bulan' => '2026-04-10']))
            ->assertJsonValidationErrors('bulan', 'errors');

        $this->assertDatabaseCount('kpi_sampah', 2);
        $this->assertDatabaseHas('kpi_sampah', ['bulan' => '2026-04-01', 'timbulan' => 500]);
    }

    public function test_cannot_move_other_users_sampah_via_update(): void
    {
        $this->loginKetua();
        $lain = \App\Models\User::factory()->role('mahasiswa')->create();
        $milikLain = $this->sisa($lain->email, '2026-03-01');
        $capaianLain = Kpicapaian::factory()->create(['email' => $lain->email, 'id_kpi' => $this->kpi->id_kpi, 'bulan' => '2026-03-01']);

        $this->put('kpicapaian/update', $this->payload(['id_capaian' => $capaianLain->id_capaian, 'bulan' => '2026-05-01']));
        $this->put('kpicapaian/destroy', ['id_capaian' => $capaianLain->id_capaian]);

        $this->assertSame('2026-03-01', $milikLain->fresh()->bulan->toDateString());
        $this->assertNotNull($capaianLain->fresh());
    }

    public function test_insert_rolls_back_capaian_when_sampah_save_fails(): void
    {
        $this->loginKetua();
        $this->partialMock(KpiSampahService::class, function ($mock) {
            $mock->shouldReceive('simpanDariCapaian')->andThrow(new \RuntimeException('gagal'));
        });

        $this->put('kpicapaian/insert', $this->payload())->assertStatus(500);

        $this->assertDatabaseCount('kpi_capaian', 0);
        $this->assertDatabaseCount('kpi_sampah', 0);
    }

    public function test_destroy_rolls_back_when_sampah_delete_fails(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload())->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();

        $this->partialMock(KpiSampahService::class, function ($mock) {
            $mock->shouldReceive('hapusDariCapaian')->andThrow(new \RuntimeException('gagal'));
        });

        $this->put('kpicapaian/destroy', ['id_capaian' => $capaian->id_capaian])->assertStatus(500);

        $this->assertNotNull($capaian->fresh());
        $this->assertDatabaseCount('kpi_sampah', 1);
    }

    public function test_insert_without_desa_rejected_and_nothing_saved(): void
    {
        $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        // Ketua tanpa Pjdesa: ditolak validasi capaian
        $this->put('kpicapaian/insert', $this->payload())->assertJsonPath('success', false);

        $this->assertDatabaseCount('kpi_capaian', 0);
        $this->assertDatabaseCount('kpi_sampah', 0);
    }

    public function test_forms_render_without_undefined_variable(): void
    {
        $user = $this->loginKetua();

        $this->get('kpicapaian/tambah')->assertOk()
            ->assertSee('name="timbulan"', false)
            ->assertSee('data-sampah-form', false)
            ->assertDontSee('name="bulan" type="month"', false);

        // Edit tanpa baris sampah → field kosong, tidak error
        $capaian = Kpicapaian::factory()->create(['email' => $user->email, 'id_kpi' => $this->kpi->id_kpi, 'bulan' => '2026-02-01']);
        $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk()
            ->assertSee('name="timbulan"', false)
            ->assertViewHas('sampah', null);

        // Edit dengan baris sampah → field terisi
        $this->sisa($user->email, '2026-02-01');
        $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk()
            ->assertSee('name="timbulan" id="sampah-timbulan" class="form-control form-control-sm" required min="0" max="9999999999" step="0.01" value="1"', false);

        // Form menu lama tetap render
        $this->get('kpisampah/tambah')->assertOk()->assertSee('name="timbulan"', false);
    }

    public function test_forms_render_when_desa_missing(): void
    {
        $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);

        $this->get('kpicapaian/tambah')->assertOk()
            ->assertViewHas('desa', null)
            ->assertSee('alert-warning', false);
    }

    public function test_sampah_list_has_no_action_column(): void
    {
        $user = $this->loginKetua();
        $this->sisa($user->email, '2026-02-01');

        $list = $this->get('kpisampah/listdata')->assertOk()->assertDontSee('>Aksi<', false)->getContent();
        // Header bertingkat: hanya th daun (tanpa colspan) yang sejajar dengan kolom DataTables
        $this->assertSame(preg_match_all('/<th(?![^>]*colspan)[ >]/', $list), substr_count($list, '{data:'));
        $this->assertStringNotContainsString("data: 'action'", $list);

        $json = $this->getJson('kpisampah/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('action', $json);
        foreach (['jml_rw', 'jml_penduduk', 'organik_sumber', 'organik_dlh', 'anorganik_sumber', 'pengurangan', 'belum_terkelola', 'keterangan'] as $kolom) {
            $this->assertArrayHasKey($kolom, $json);
        }

        $this->get('kpicapaian')->assertOk()->assertDontSee('Tambah Data Sampah');
        $this->get('kpisampah')->assertOk();
    }
}
