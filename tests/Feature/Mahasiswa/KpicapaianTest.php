<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpisampah;
use App\Http\Requests\Mahasiswa\KpicapaianRequest;
use App\Models\Pjdesa;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpicapaianTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, Kpi> */
    private array $kpi = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1, 2] as $i) {
            $this->kpi[$i] = Kpi::factory()->create(['nama_kpi' => 'KPI '.$i]);
        }
    }

    private function loginKetua()
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function payload(int $kpi, array $override = []): array
    {
        return $override + [
            'id_kpi' => $this->kpi[$kpi]->id_kpi,
            'bulan' => now()->toDateString(),
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
            'jml_rw' => 5,
            'jml_penduduk' => 800,
            'jml_rumah' => 200,
            'jml_rumah_memilah' => 50,
            'timbulan' => 1000,
            'organik_sumber' => 200,
            'organik_metode_unit' => 1,
            'organik_dlh' => 100,
            'anorganik_sumber' => 100,
            'anorganik_metode_unit' => 1,
        ];
    }

    public function test_insert_saves_sampah_in_same_month_with_server_values(): void
    {
        $user = $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-03-17', 'pengurangan' => 999]))
            ->assertJson(['success' => true]);

        $sampah = Kpisampah::firstOrFail();
        $this->assertSame('2026-03-01', $sampah->bulan->toDateString());
        $this->assertSame($user->email, $sampah->email);
        $this->assertSame(app(\App\Services\KpiSampahService::class)->desaKetua($user->email), $sampah->id_desa);
        $this->assertSame(400.0, $sampah->pengurangan);
        $this->assertSame(600.0, $sampah->belum_terkelola);
        $this->assertSame(40.0, $sampah->persen_pengurangan);
        $this->assertSame(25.0, $sampah->persen_ketaatan);
    }

    public function test_sampah_invalid_rejects_whole_submit(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['organik_sumber' => 850]))
            ->assertJsonValidationErrors('anorganik_sumber', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['jml_rumah_memilah' => 201]))
            ->assertJsonValidationErrors('jml_rumah_memilah', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['timbulan' => '']))
            ->assertJsonValidationErrors('timbulan', 'errors');

        $this->assertDatabaseCount('kpi_capaian', 0);
        $this->assertDatabaseCount('kpi_sampah', 0);
    }

    public function test_update_moves_sampah_to_new_month_and_overwrites_leftover(): void
    {
        $user = $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-03-10']))->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();
        $idSampah = Kpisampah::firstOrFail()->id_sampah;
        // Sisa input menu lama di bulan tujuan
        Kpisampah::create(['email' => $user->email, 'bulan' => '2026-05-01', 'id_desa' => Desa::factory()->create()->id_desa,
            'jml_rw' => 0, 'jml_penduduk' => 0, 'jml_rumah' => 0, 'jml_rumah_memilah' => 0, 'timbulan' => 1,
            'organik_sumber' => 0, 'organik_dlh' => 0, 'anorganik_sumber' => 0, 'pengurangan' => 0, 'belum_terkelola' => 1]);

        $this->put('kpicapaian/update', $this->payload(1, [
            'id_capaian' => $capaian->id_capaian, 'bulan' => '2026-05-09', 'timbulan' => 2000,
        ]))->assertJson(['success' => true]);

        $this->assertDatabaseCount('kpi_sampah', 1);
        $sampah = Kpisampah::firstOrFail();
        $this->assertSame($idSampah, $sampah->id_sampah);
        $this->assertSame('2026-05-01', $sampah->bulan->toDateString());
        $this->assertSame(2000.0, $sampah->timbulan);
    }

    public function test_update_creates_sampah_when_missing_and_edit_prefills(): void
    {
        $user = $this->loginKetua();
        $capaian = Kpicapaian::factory()->create(['email' => $user->email, 'id_kpi' => $this->kpi[1]->id_kpi, 'bulan' => '2026-02-01']);

        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $capaian->id_capaian, 'bulan' => '2026-02-01']))
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('kpi_sampah', ['email' => $user->email, 'bulan' => '2026-02-01', 'timbulan' => 1000]);

        $response = $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk();
        $this->assertSame(1000.0, $response->viewData('sampah')->timbulan);
        $this->assertNotNull($response->viewData('desa'));
        $this->assertNotNull($this->get('kpicapaian/tambah')->viewData('desa'));
    }

    public function test_destroy_deletes_sampah_of_same_month_only(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-03-10']))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-04-10']))->assertJson(['success' => true]);
        $maret = Kpicapaian::where('bulan', '2026-03-01')->firstOrFail();

        $this->put('kpicapaian/destroy', ['id_capaian' => $maret->id_capaian])->assertJson(['success' => true]);

        $this->assertDatabaseCount('kpi_sampah', 1);
        $this->assertDatabaseHas('kpi_sampah', ['bulan' => '2026-04-01']);
    }

    public function test_ketua_creates_one_capaian_per_month(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(2))->assertJson(['success' => true]);
        // KPI lain di bulan yang sama tetap ditolak: aturan per bulan, bukan per KPI
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => now()->startOfMonth()->toDateString()]))
            ->assertJsonPath('errors.bulan.0', KpicapaianRequest::DUPLIKAT_BULAN);

        $this->assertSame(1, Kpicapaian::count());

        $bulanLalu = now()->startOfMonth()->subMonthNoOverflow()->toDateString();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => $bulanLalu]))->assertJson(['success' => true]);
        $this->assertSame(2, Kpicapaian::count());
    }

    public function test_bulan_saved_as_start_of_month_and_editable(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-03-17']))->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();
        $this->assertSame('2026-03-01', Carbon::parse($capaian->bulan)->toDateString());

        $this->put('kpicapaian/update', $this->payload(2, [
            'id_capaian' => $capaian->id_capaian, 'solusi' => 'Solusi baru', 'bulan' => '2026-05-09',
        ]))->assertJson(['success' => true]);

        $capaian->refresh();
        $this->assertSame('Solusi baru', $capaian->solusi);
        $this->assertSame($this->kpi[2]->id_kpi, $capaian->id_kpi);
        $this->assertSame('2026-05-01', Carbon::parse($capaian->bulan)->toDateString());

        // Update tanpa ganti bulan tidak dianggap duplikat dirinya sendiri
        $this->put('kpicapaian/update', $this->payload(2, ['id_capaian' => $capaian->id_capaian, 'bulan' => '2026-05-20']))
            ->assertJson(['success' => true]);
    }

    public function test_bulan_required_and_not_in_future(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '']))->assertJsonValidationErrors('bulan', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => 'bukan-tanggal']))->assertJsonValidationErrors('bulan', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => now()->addDay()->toDateString()]))
            ->assertJsonValidationErrors('bulan', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => now()->toDateString()]))->assertJson(['success' => true]);
    }

    public function test_bulan_before_2020_rejected(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2019-12-31']))
            ->assertJsonPath('errors.bulan.0', 'Tanggal minimal Januari 2020.');
        $this->assertDatabaseCount('kpi_sampah', 0);
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2020-01-01']))->assertJson(['success' => true]);
    }

    public function test_update_to_existing_month_is_rejected(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-01-10']))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-02-10']))->assertJson(['success' => true]);
        $feb = Kpicapaian::where('bulan', '2026-02-01')->firstOrFail();

        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $feb->id_capaian, 'bulan' => '2026-01-25']))
            ->assertJsonPath('errors.bulan.0', KpicapaianRequest::DUPLIKAT_BULAN);
        $this->assertSame('2026-02-01', Carbon::parse($feb->refresh()->bulan)->toDateString());
    }

    public function test_ketua_can_edit_and_delete_own_capaian(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload(1))->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();

        $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk();
        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $capaian->id_capaian]))->assertJson(['success' => true]);
        $this->put('kpicapaian/destroy', ['id_capaian' => $capaian->id_capaian])->assertJson(['success' => true]);
        $this->assertDatabaseCount('kpi_capaian', 0);
    }

    public function test_form_has_no_kegiatan_or_realisasi(): void
    {
        $this->loginKetua();

        $this->get('kpicapaian/tambah')->assertOk()->assertSee('KPI 1')
            ->assertDontSee('name="id_target"', false)->assertDontSee('name="realisasi"', false);
    }

    public function test_required_fields_and_unknown_columns_ignored(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['permasalahan' => '']))->assertJsonValidationErrors('permasalahan', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['status_capaian' => 'X']))->assertJsonValidationErrors('status_capaian', 'errors');

        $this->put('kpicapaian/insert', $this->payload(1, ['email' => 'orang-lain@pps.test']))->assertJson(['success' => true]);
        $this->assertNotSame('orang-lain@pps.test', Kpicapaian::firstOrFail()->email);
    }

    public function test_non_ketua_cannot_create_capaian(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('kpicapaian/insert', $this->payload(1))->assertJsonValidationErrors('kendala', 'errors');
        $this->assertDatabaseCount('kpi_capaian', 0);
    }

    public function test_qc_non_ketua_cannot_update_capaian_with_own_email(): void
    {
        $user = $this->loginAs('mahasiswa');
        $capaian = Kpicapaian::factory()->create(['email' => $user->email, 'id_kpi' => $this->kpi[1]->id_kpi]);

        $this->put('kpicapaian/update', $this->payload(2, ['id_capaian' => $capaian->id_capaian]))
            ->assertJsonValidationErrors('kendala', 'errors');
        $this->assertSame($this->kpi[1]->id_kpi, $capaian->refresh()->id_kpi);
    }

    public function test_qc_edit_form_prefills_bulan_and_limits_future(): void
    {
        $this->loginKetua();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => '2026-03-17']))->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();

        $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk()
            ->assertSee('value="2026-03-01"', false)
            ->assertSee('max="'.now()->toDateString().'"', false);
        $this->get('kpicapaian/tambah')->assertOk()->assertSee('name="bulan"', false);
    }

    public function test_cannot_edit_or_delete_other_students_capaian(): void
    {
        $capaianLain = Kpicapaian::factory()->create([
            'email' => 'ketua-lain@pps.test',
            'id_kpi' => $this->kpi[1]->id_kpi,
        ]);
        $this->loginKetua();

        $this->get('kpicapaian/edit/'.$capaianLain->id_capaian)->assertNotFound();
        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $capaianLain->id_capaian]))->assertNotFound();
        $this->put('kpicapaian/destroy', ['id_capaian' => $capaianLain->id_capaian])->assertNotFound();
        $this->assertDatabaseHas('kpi_capaian', ['id_capaian' => $capaianLain->id_capaian, 'solusi' => $capaianLain->solusi]);
    }

    public function test_listdata_escapes_free_text(): void
    {
        $user = $this->loginKetua();
        Kpicapaian::factory()->create([
            'email' => $user->email,
            'id_kpi' => $this->kpi[1]->id_kpi,
            'permasalahan' => '<img src=x onerror=alert(1)>',
            'tautan' => 'javascript:alert(1)',
        ]);

        $row = $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');

        $this->assertStringNotContainsString('<img', $row['permasalahan']);
        $this->assertSame(now()->startOfMonth()->translatedFormat('F Y'), $row['bulan_label']);
        $this->assertSame('', $row['tautan']);
    }
}
