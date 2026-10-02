<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Pjdesa;
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
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
        ];
    }

    public function test_ketua_fills_each_kpi_once_in_any_order(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(2))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1))->assertJsonPath('errors.id_kpi.0', 'Data sudah ada!');

        $this->assertSame(2, Kpicapaian::count());
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
        $this->assertSame('', $row['tautan']);
    }
}
