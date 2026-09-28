<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Kpitarget;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\KpiRekapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    private LokasiProgram $lokasi;

    private Satuanpendidikan $pt1;

    private Satuanpendidikan $pt2;

    private Kpitarget $target;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lokasi = LokasiProgram::factory()->create();
        $this->pt1 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Satu']);
        $this->pt2 = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Dua']);
        $this->target = Kpitarget::factory()->create([
            'id_kpi' => Kpi::factory()->create()->id_kpi,
            'tahapan' => 'Tahap 1',
            'target' => 80,
            'satuan' => '%',
        ]);

        // PT1: 2 kelompok (90 => 100%, belum isi => 0%), PT2: 1 kelompok (40 => 50%)
        $this->ketua($this->pt1, 90);
        $this->ketua($this->pt1, null);
        $this->ketua($this->pt2, 40);
    }

    private function ketua(Satuanpendidikan $pt, ?float $realisasi): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $this->lokasi->id]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        if ($realisasi !== null) {
            Kpicapaian::factory()->create([
                'email' => $mhs->email,
                'id_kpi' => $this->target->id_kpi,
                'id_target' => $this->target->id_target,
                'realisasi' => $realisasi,
                'satuan' => '%',
            ]);
        }

        return $mhs;
    }

    public function test_capaian_is_capped_and_unfilled_groups_count_as_zero(): void
    {
        $service = app(KpiRekapService::class);
        $rows = $service->rekapPerPt([])->keyBy('kodept');

        $this->assertSame(2, (int) $rows[$this->pt1->npsn]->jumlah_kelompok);
        $this->assertSame(1, (int) $rows[$this->pt1->npsn]->jumlah_mengisi);
        $this->assertEquals(50, $rows[$this->pt1->npsn]->capaian);
        $this->assertEquals(50, $rows[$this->pt2->npsn]->capaian);

        $summary = $service->summary([]);
        $this->assertSame(2, $summary['jumlah_pt']);
        $this->assertSame(3, $summary['total_kelompok']);
        $this->assertSame(3, $summary['total_kelurahan']);
        $this->assertEquals(50, $summary['rata_capaian']);
    }

    public function test_kepala_home_redirects_to_dashboard_and_sees_all_pt(): void
    {
        $this->loginAs('kepala');

        $this->get('home')->assertRedirect(route('dashboardkpi'));
        $this->get('dashboardkpi')->assertOk()->assertSee('Universitas Satu')->assertSee('Politeknik Dua');
        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('rekapPerPt', fn ($rows) => $rows->pluck('kodept')->unique()->all() === [$this->pt2->npsn]);
    }

    public function test_pt_is_locked_to_its_own_pt_and_lokasi(): void
    {
        $this->loginAs('pt', ['email' => $this->pt1->npsn, 'location_program' => $this->lokasi->id]);

        $this->get('dashboardkpi?kodept='.$this->pt2->npsn)->assertOk()
            ->assertViewHas('filter', fn ($f) => $f['kodept'] === $this->pt1->npsn && $f['lokasi'] === $this->lokasi->id)
            ->assertViewHas('rekapPerPt', fn ($rows) => $rows->pluck('kodept')->unique()->all() === [$this->pt1->npsn])
            ->assertSee('Belum diisi');
    }

    public function test_dashboard_rejects_invalid_filter(): void
    {
        $this->loginAs('kepala');

        $this->get('dashboardkpi?lokasi=bukan-uuid')->assertSessionHasErrors('lokasi');
    }

    public function test_kepala_cannot_access_admin_or_mahasiswa_routes(): void
    {
        $this->loginAs('kepala');

        $this->get('kpitarget')->assertRedirect(route('home'));
        $this->get('user')->assertRedirect(route('home'));
        $this->put('kpicapaian/insert', [])->assertRedirect(route('home'));
        $this->get('lapcapaiankpi')->assertOk();
    }

    public function test_dpl_and_mahasiswa_cannot_open_dashboard(): void
    {
        $this->loginAs('mahasiswa');

        $this->get('dashboardkpi')->assertRedirect(route('home'));
    }

    public function test_admin_can_create_and_update_kepala_user(): void
    {
        $this->loginAs('admin');

        $this->put('user/insertuserkepala', [
            'name' => 'Kepala LLDIKTI',
            'email' => 'kepala@pps.test',
            'password' => 'rahasia123',
            'role' => 'admin',
        ])->assertJson(['success' => true]);

        $kepala = User::where('email', 'kepala@pps.test')->firstOrFail();
        $this->assertSame('kepala', $kepala->role);

        $this->put('user/insertuserkepala', ['name' => 'X', 'email' => 'kepala@pps.test', 'password' => 'rahasia123'])
            ->assertJsonValidationErrors('email', 'errors');

        $pt = User::factory()->role('pt')->create();
        $this->put('user/updateuserkepala', ['id' => $pt->id, 'name' => 'X', 'email' => 'x@pps.test'])
            ->assertJsonValidationErrors('id', 'errors');

        $this->put('user/updateuserkepala', ['id' => $kepala->id, 'name' => 'Kepala Baru', 'email' => 'kepala@pps.test'])
            ->assertJson(['success' => true]);
        $this->assertSame('Kepala Baru', $kepala->fresh()->name);
    }
}
