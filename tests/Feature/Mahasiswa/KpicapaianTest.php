<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\Pjdesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpicapaianTest extends TestCase
{
    use RefreshDatabase;

    private Kpi $kpi;

    /** @var array<int, Kpitarget> */
    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->kpi = Kpi::factory()->create();
        foreach ([1, 2] as $i) {
            $this->targets[$i] = Kpitarget::factory()->create(['id_kpi' => $this->kpi->id_kpi, 'kegiatan' => 'Kegiatan '.$i]);
        }
    }

    private function loginKetua()
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function payload(int $tahap, array $override = []): array
    {
        return $override + [
            'id_kpi' => $this->kpi->id_kpi,
            'id_target' => $this->targets[$tahap]->id_target,
            'realisasi' => 20,
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
        ];
    }

    public function test_ketua_fills_each_kegiatan_once_in_any_order(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(2))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1))->assertJson(['success' => true]);
        $this->put('kpicapaian/insert', $this->payload(1))->assertJsonPath('errors.id_target.0', 'Data sudah ada!');

        $this->assertSame(2, Kpicapaian::count());
    }

    public function test_kegiatan_dropdown_renders_after_choosing_kpi(): void
    {
        $this->loginKetua();

        $this->get('kpicapaian/tambah')->assertOk();
        $this->post('kpicapaian/kpitarget', ['id_kpi' => $this->kpi->id_kpi])
            ->assertOk()->assertSee('Kegiatan 1')->assertSee('data-satuan="%"', false);
    }

    public function test_realisasi_required_and_satuan_follows_target(): void
    {
        $this->loginKetua();

        $this->put('kpicapaian/insert', $this->payload(1, ['realisasi' => '']))->assertJsonValidationErrors('realisasi', 'errors');
        $this->put('kpicapaian/insert', $this->payload(1, ['realisasi' => -5]))->assertJsonValidationErrors('realisasi', 'errors');

        // Realisasi boleh melebihi target; capaian hanya dari tindak lanjut Sudah Selesai
        $this->put('kpicapaian/insert', $this->payload(1, ['realisasi' => 90, 'satuan' => 'palsu']))->assertJson(['success' => true]);
        $capaian = Kpicapaian::firstOrFail();
        $this->assertSame('%', $capaian->satuan);
        $this->assertEquals(90, $capaian->realisasi);
        $this->assertEquals(0, $capaian->capaianPersen());

        $capaian->update(['status_capaian' => 'Y']);
        $this->assertEquals(100, $capaian->capaianPersen());
    }

    public function test_non_ketua_cannot_create_capaian(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('kpicapaian/insert', $this->payload(1))->assertJsonValidationErrors('kendala', 'errors');
        $this->assertDatabaseCount('kpi_capaian', 0);
    }

    public function test_target_must_belong_to_selected_kpi(): void
    {
        $this->loginKetua();
        $lain = Kpitarget::factory()->create(['id_kpi' => Kpi::factory()->create()->id_kpi]);

        $this->put('kpicapaian/insert', $this->payload(1, ['id_target' => $lain->id_target]))
            ->assertJsonPath('errors.id_target.0', 'Kegiatan tidak sesuai dengan KPI yang dipilih.');
    }

    public function test_cannot_edit_or_delete_other_students_capaian(): void
    {
        $capaianLain = Kpicapaian::factory()->create([
            'email' => 'ketua-lain@pps.test',
            'id_kpi' => $this->kpi->id_kpi,
            'id_target' => $this->targets[1]->id_target,
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
            'id_kpi' => $this->kpi->id_kpi,
            'id_target' => $this->targets[1]->id_target,
            'permasalahan' => '<img src=x onerror=alert(1)>',
            'tautan' => 'javascript:alert(1)',
        ]);

        $row = $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');

        $this->assertStringNotContainsString('<img', $row['permasalahan']);
        $this->assertSame('', $row['tautan']);
    }
}
