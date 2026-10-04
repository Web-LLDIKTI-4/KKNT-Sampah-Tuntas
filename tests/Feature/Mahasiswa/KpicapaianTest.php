<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
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
        ];
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
